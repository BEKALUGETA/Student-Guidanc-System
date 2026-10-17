<?php
include 'includes/header.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if viewing a specific department
if(isset($_GET['dept'])) {
    $dept_code = $_GET['dept'];
    
    $query = "SELECT * FROM departments WHERE dept_code = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$dept_code]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($department) {
        // Get media
        $image_url = $department['image_url'] ?? '';
        $video_url = $department['video_url'] ?? '';
        $video_title = $department['video_title'] ?? '';
        $video_description = $department['video_description'] ?? '';
        $gallery_images = !empty($department['gallery_images']) ? explode(',', $department['gallery_images']) : [];
        
        // Get courses
        $course_stmt = $db->prepare("SELECT * FROM courses WHERE dept_id = ?");
        $course_stmt->execute([$department['dept_id']]);
        $courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get careers
        $career_stmt = $db->prepare("SELECT * FROM careers WHERE dept_id = ?");
        $career_stmt->execute([$department['dept_id']]);
        $careers = $career_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get role models
        $model_stmt = $db->prepare("SELECT * FROM role_models WHERE dept_id = ?");
        $model_stmt->execute([$department['dept_id']]);
        $models = $model_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Department icon
        $icon = $dept_code == 'EE' ? 'microchip' : ($dept_code == 'CS' ? 'laptop-code' : ($dept_code == 'IS' ? 'chart-line' : 'cogs'));
        ?>
        
        <div class="department-detail">
            <!-- Department Header -->
            <div class="department-header">
                <i class="fas fa-<?php echo $icon; ?> fa-3x" style="color: <?php echo $department['color_code'] ?? '#3b82f6'; ?>;"></i>
                <div>
                    <h1><?php echo htmlspecialchars($department['dept_name']); ?></h1>
                    <p style="color: #6b7280;">Department Code: <?php echo htmlspecialchars($department['dept_code']); ?></p>
                </div>
            </div>
            
            <!-- ============ BANNER IMAGE ============ -->
            <?php if(!empty($image_url)): ?>
                <?php 
                // Check if file exists
                $banner_path = $image_url;
                if(file_exists($banner_path)): 
                ?>
                <div class="department-banner">
                    <img src="<?php echo htmlspecialchars($image_url); ?>" 
                         alt="<?php echo htmlspecialchars($department['dept_name']); ?> Banner"
                         onerror="this.style.display='none'">
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- ============ INTRODUCTION VIDEO ============ -->
            <?php if(!empty($video_url)): ?>
            <div style="background: white; border-radius: 15px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-play-circle" style="color: #ef4444; font-size: 1.5rem;"></i> 
                    <?php echo htmlspecialchars($video_title ?: 'Department Introduction Video'); ?>
                </h3>
                <div class="video-container">
                    <iframe src="<?php echo htmlspecialchars($video_url); ?>" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                    </iframe>
                </div>
                <?php if(!empty($video_description)): ?>
                <p style="color: #6b7280; margin-top: 1rem; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($video_description)); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- ============ GALLERY IMAGES ============ -->
            <?php if(!empty($gallery_images)): ?>
            <div style="background: white; border-radius: 15px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-images" style="color: #8b5cf6; font-size: 1.5rem;"></i> 
                    Department Gallery
                </h3>
                <div class="gallery-grid">
                    <?php foreach($gallery_images as $img): 
                        $img = trim($img);
                        if(!empty($img) && file_exists($img)):
                    ?>
                    <div class="gallery-item" onclick="openLightbox('<?php echo htmlspecialchars($img); ?>')">
                        <img src="<?php echo htmlspecialchars($img); ?>" 
                             alt="Gallery Image" 
                             onerror="this.style.display='none'">
                    </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
            
            <!-- Lightbox for Gallery -->
            <div class="lightbox" id="lightbox" onclick="closeLightbox()">
                <span class="lightbox-close">&times;</span>
                <img id="lightbox-img" src="" alt="Full Size Image">
            </div>
            <?php endif; ?>
            
            <!-- ============ TABS: Overview, Courses, Careers, Models ============ -->
            <div class="tabs">
                <button class="tab-btn active" data-tab="overview">📋 Overview</button>
                <button class="tab-btn" data-tab="courses">📚 Courses</button>
                <button class="tab-btn" data-tab="careers">💼 Career Paths</button>
                <button class="tab-btn" data-tab="models">⭐ Role Models</button>
            </div>
            
            <!-- Overview Tab -->
            <div id="overview" class="tab-content active">
                <h3>About <?php echo htmlspecialchars($department['dept_name']); ?></h3>
                <p style="line-height: 1.8;"><?php echo nl2br(htmlspecialchars($department['description'])); ?></p>
                
                <h3 style="margin-top: 2rem;">Required Skills</h3>
                <p style="line-height: 1.8;"><?php echo nl2br(htmlspecialchars($department['required_skills'])); ?></p>
                
                <h3 style="margin-top: 2rem;">Natural Requirements</h3>
                <p style="line-height: 1.8;"><?php echo nl2br(htmlspecialchars($department['natural_requirements'])); ?></p>
            </div>
            
            <!-- Courses Tab -->
            <div id="courses" class="tab-content">
                <h3>Course Titles for <?php echo htmlspecialchars($department['dept_name']); ?></h3>
                <?php if(!empty($courses)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Title</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($courses as $course): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($course['course_title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($course['course_description']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p style="color: #6b7280; text-align: center; padding: 2rem;">No courses listed yet.</p>
                <?php endif; ?>
            </div>
            
            <!-- Careers Tab -->
            <div id="careers" class="tab-content">
                <h3>Career Paths & Job Opportunities</h3>
                <?php if(!empty($careers)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Career Path</th>
                                <th>Description</th>
                                <th>Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($careers as $career): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($career['career_title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($career['career_description']); ?></td>
                                <td>
                                    <span style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;
                                        <?php 
                                        echo $career['job_level'] == 'Entry' ? 'background:#dbeafe;color:#1e40af;' : 
                                             ($career['job_level'] == 'Mid' ? 'background:#fef3c7;color:#92400e;' : 
                                             'background:#d1fae5;color:#065f46;'); 
                                        ?>">
                                        <?php echo htmlspecialchars($career['job_level']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p style="color: #6b7280; text-align: center; padding: 2rem;">No career paths listed yet.</p>
                <?php endif; ?>
            </div>
            
            <!-- Role Models Tab -->
            <div id="models" class="tab-content">
                <h3>Role Models in <?php echo htmlspecialchars($department['dept_name']); ?></h3>
                <?php if(!empty($models)): ?>
                <div class="card-grid">
                    <?php foreach($models as $model): ?>
                    <div class="card">
                        <h4 style="font-size: 1.2rem;"><?php echo htmlspecialchars($model['name']); ?></h4>
                        <p style="color: #3b82f6; font-weight: 600;"><?php echo htmlspecialchars($model['achievement']); ?></p>
                        <p style="color: #6b7280;"><?php echo htmlspecialchars($model['description']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color: #6b7280; text-align: center; padding: 2rem;">No role models listed yet.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- JavaScript for Tabs and Lightbox -->
        <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                document.getElementById(this.getAttribute('data-tab')).classList.add('active');
            });
        });
        
        // Lightbox for gallery
        function openLightbox(src) {
            document.getElementById('lightbox').classList.add('active');
            document.getElementById('lightbox-img').src = src;
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
        }
        // Close lightbox with Escape key
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') closeLightbox();
        });
        </script>
        
        <?php
    } else {
        echo '<div class="alert alert-error">Department not found.</div>';
    }
} else {
    // Show all departments listing
    $query = "SELECT * FROM departments ORDER BY dept_name";
    $stmt = $db->query($query);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <h1 style="text-align: center; margin: 2rem 0; font-size: 2.5rem;">
        <i class="fas fa-building"></i> Explore Departments
    </h1>
    <p style="text-align: center; color: var(--gray-600); margin-bottom: 2rem; font-size: 1.1rem;">
        Discover which department aligns with your interests and career goals
    </p>
    
    <div class="card-grid">
        <?php foreach($departments as $dept): 
            $icon = $dept['dept_code'] == 'EE' ? 'microchip' : ($dept['dept_code'] == 'CS' ? 'laptop-code' : ($dept['dept_code'] == 'IS' ? 'chart-line' : 'cogs'));
        ?>
        <div class="card" style="border-left: 4px solid <?php echo $dept['color_code'] ?? '#3b82f6'; ?>;">
            <!-- Show department image if uploaded, otherwise show icon -->
            <?php if(!empty($dept['image_url']) && file_exists($dept['image_url'])): ?>
                <img src="<?php echo htmlspecialchars($dept['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($dept['dept_name']); ?>" 
                     style="width: 100%; height: 180px; object-fit: cover; border-radius: 10px; margin-bottom: 1rem;"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="card-icon <?php echo strtolower($dept['dept_code']); ?>" style="display: none;">
                    <i class="fas fa-<?php echo $icon; ?>"></i>
                </div>
            <?php else: ?>
                <div class="card-icon <?php echo strtolower($dept['dept_code']); ?>">
                    <i class="fas fa-<?php echo $icon; ?>"></i>
                </div>
            <?php endif; ?>
            
            <h3><?php echo htmlspecialchars($dept['dept_name']); ?></h3>
            <p><?php echo htmlspecialchars(substr($dept['description'], 0, 150)) . '...'; ?></p>
            <a href="departments.php?dept=<?php echo $dept['dept_code']; ?>" class="btn btn-outline">
                View Details <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php
}
?>

<!-- Additional CSS for media display -->
<style>
    .department-banner {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        margin-bottom: 2rem;
    }
    .department-banner img {
        width: 100%;
        max-height: 400px;
        object-fit: cover;
        display: block;
    }
    .video-container {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        max-width: 100%;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    }
    .video-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 10px;
    }
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    .gallery-item {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.3s;
        cursor: pointer;
    }
    .gallery-item:hover {
        transform: scale(1.05);
    }
    .gallery-item img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        display: block;
    }
    .lightbox {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        z-index: 9999;
        cursor: pointer;
    }
    .lightbox.active {
        display: block;
    }
    .lightbox-close {
        position: absolute;
        top: 20px;
        right: 40px;
        color: white;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        z-index: 10000;
    }
    .lightbox-close:hover {
        color: #ef4444;
    }
    .lightbox img {
        max-width: 90%;
        max-height: 90%;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        border-radius: 10px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .gallery-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
        .gallery-item img {
            height: 120px;
        }
        .department-banner img {
            max-height: 200px;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>