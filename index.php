<?php
include 'includes/header.php';
require_once 'config/database.php';

// Get all departments from database
$database = new Database();
$db = $database->getConnection();
$departments = $db->query("SELECT * FROM departments ORDER BY dept_name")->fetchAll(PDO::FETCH_ASSOC);

// Department icon mapping
function getDeptIcon($code) {
    $icons = [
        'EE' => 'microchip',
        'CS' => 'laptop-code',
        'IS' => 'chart-line',
        'SW' => 'cogs'
    ];
    return $icons[$code] ?? 'building';
}
?>

<!-- Hero Section -->
<section class="hero">
    <h1>Find Your Perfect Academic Path</h1>
    <p>Discover which department aligns with your interests, skills, and career goals. Make an informed decision about your future.</p>
    <div class="hero-buttons">
        <?php if(!isset($_SESSION['user_id'])): ?>
            <a href="register.php" class="btn btn-primary btn-lg">
                <i class="fas fa-rocket"></i> Get Started Free
            </a>
            <a href="departments.php" class="btn btn-secondary btn-lg">
                <i class="fas fa-compass"></i> Explore Departments
            </a>
        <?php else: ?>
            <a href="recommendation.php" class="btn btn-primary btn-lg">
                <i class="fas fa-lightbulb"></i> Get Your Recommendation
            </a>
            <a href="departments.php" class="btn btn-secondary btn-lg">
                <i class="fas fa-compass"></i> Browse Departments
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- Department Cards Section -->
<h2 style="text-align: center; font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--gray-900);">
    Choose Your Department
</h2>
<p style="text-align: center; color: var(--gray-600); margin-bottom: 2rem; font-size: 1.1rem;">
    Explore our <?php echo count($departments); ?> academic departments and find your perfect fit
</p>

<div class="card-grid">
    <?php if(empty($departments)): ?>
        <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: #6b7280;">
            <i class="fas fa-building" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
            <p>No departments available yet. Please check back later.</p>
        </div>
    <?php else: ?>
        <?php foreach($departments as $dept): 
            $icon = getDeptIcon($dept['dept_code']);
            $color = $dept['color_code'] ?? '#3b82f6';
        ?>
        <div class="card" style="border-left: 4px solid <?php echo $color; ?>;">
            <!-- Show uploaded image if available, otherwise show icon -->
            <?php if(!empty($dept['image_url']) && file_exists($dept['image_url'])): ?>
                <img src="<?php echo htmlspecialchars($dept['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($dept['dept_name']); ?>"
                     style="width: 100%; height: 180px; object-fit: cover; border-radius: 10px; margin-bottom: 1rem;"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="card-icon" style="display: none; background: <?php echo $color; ?>20; color: <?php echo $color; ?>;">
                    <i class="fas fa-<?php echo $icon; ?>"></i>
                </div>
            <?php else: ?>
                <div class="card-icon" style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>;">
                    <i class="fas fa-<?php echo $icon; ?>"></i>
                </div>
            <?php endif; ?>
            
            <h3><?php echo htmlspecialchars($dept['dept_name']); ?></h3>
            <p><?php echo htmlspecialchars(substr($dept['description'], 0, 150)) . '...'; ?></p>
            <p style="font-size: 0.85rem; color: <?php echo $color; ?>; font-weight: 600;">
                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($dept['dept_code']); ?>
            </p>
            <a href="departments.php?dept=<?php echo $dept['dept_code']; ?>" class="btn btn-outline btn-sm">
                Explore <?php echo htmlspecialchars($dept['dept_code']); ?> <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- How It Works Section -->
<?php if(!isset($_SESSION['user_id'])): ?>
<div style="background: var(--white); border-radius: var(--radius-2xl); padding: 3rem 2rem; margin: 3rem 0; box-shadow: var(--shadow-md);">
    <h2 style="text-align: center; font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">How It Works</h2>
    <p style="text-align: center; color: var(--gray-600); margin-bottom: 3rem;">Three simple steps to find your ideal department</p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
        <div style="text-align: center; padding: 2rem;">
            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #dbeafe, #bfdbfe); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                <i class="fas fa-user-plus" style="font-size: 2rem; color: #3b82f6;"></i>
            </div>
            <h3 style="margin-bottom: 0.75rem;">1. Create Account</h3>
            <p style="color: #6b7280;">Register and set up your profile with your interests and skills.</p>
        </div>
        
        <div style="text-align: center; padding: 2rem;">
            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                <i class="fas fa-clipboard-check" style="font-size: 2rem; color: #10b981;"></i>
            </div>
            <h3 style="margin-bottom: 0.75rem;">2. Get Analyzed</h3>
            <p style="color: #6b7280;">Our system analyzes your profile against department requirements.</p>
        </div>
        
        <div style="text-align: center; padding: 2rem;">
            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #ede9fe, #c4b5fd); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                <i class="fas fa-lightbulb" style="font-size: 2rem; color: #7c3aed;"></i>
            </div>
            <h3 style="margin-bottom: 0.75rem;">3. Get Recommendations</h3>
            <p style="color: #6b7280;">Receive personalized department suggestions based on your profile.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Additional CSS for dynamic cards -->
<style>
    .card-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    .card:hover .card-icon {
        transform: scale(1.1);
    }
    .btn-lg {
        padding: 1rem 2.5rem;
        font-size: 1.1rem;
    }
    .btn-sm {
        padding: 0.5rem 1.25rem;
        font-size: 0.9rem;
    }
    @media (max-width: 768px) {
        .hero h1 {
            font-size: 2rem;
        }
        .hero p {
            font-size: 1rem;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>