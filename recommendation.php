<?php
include 'includes/header.php';
require_once 'config/database.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get all departments for the recommendation system
$departments = $db->query("SELECT * FROM departments ORDER BY dept_name")->fetchAll(PDO::FETCH_ASSOC);
$dept_names = [];
$dept_scores = [];
foreach($departments as $d) {
    $dept_names[$d['dept_code']] = $d['dept_name'];
    $dept_scores[$d['dept_code']] = 0;
}

// Get user profile
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h1 style="text-align: center; margin: 2rem 0;">
        <i class="fas fa-lightbulb"></i> Get Department Recommendation
    </h1>
    
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <h3>Tell us about yourself</h3>
        <p style="color: #6b7280;">Enter your interests, skills, and academic performance to get personalized recommendations from our <?php echo count($departments); ?> departments.</p>
        
        <form id="recommendationForm">
            <div class="form-group">
                <label for="interests">Your Interests</label>
                <textarea id="interests" name="interests" rows="3" required 
                    placeholder="e.g., coding, circuits, business, web development, AI, hardware..."><?php echo isset($user['interests']) ? htmlspecialchars($user['interests']) : ''; ?></textarea>
                <small style="color: #6b7280;">List activities or subjects you enjoy</small>
            </div>
            
            <div class="form-group">
                <label for="skills">Your Skills</label>
                <textarea id="skills" name="skills" rows="3" required 
                    placeholder="e.g., programming, mathematics, communication, design, logic..."><?php echo isset($user['skills']) ? htmlspecialchars($user['skills']) : ''; ?></textarea>
                <small style="color: #6b7280;">List your technical and soft skills</small>
            </div>
            
            <div class="form-group">
                <label for="gpa">GPA (0-4 scale)</label>
                <input type="number" id="gpa" name="gpa" step="0.01" min="0" max="4" 
                    value="<?php echo isset($user['gpa']) ? htmlspecialchars($user['gpa']) : '3.0'; ?>">
                <small style="color: #6b7280;">Your current Grade Point Average (optional)</small>
            </div>
            
            <button type="submit" class="btn btn-solid-primary" style="width: 100%;">
                <i class="fas fa-search"></i> Get Recommendation
            </button>
        </form>
    </div>
    
    <div id="recommendationResult"></div>
</div>

<script>
// Pass department names from PHP to JavaScript
const deptNames = <?php echo json_encode($dept_names); ?>;
const deptCodes = Object.keys(deptNames);

document.getElementById('recommendationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const interests = document.getElementById('interests').value.toLowerCase();
    const skills = document.getElementById('skills').value.toLowerCase();
    const gpa = parseFloat(document.getElementById('gpa').value) || 0;
    
    // Initialize scores for all departments dynamically
    let scores = {};
    deptCodes.forEach(code => {
        scores[code] = 0;
    });
    
    // INTEREST-BASED SCORING (dynamic for any department)
    if(interests.includes('cod') || interests.includes('program') || interests.includes('develop') || interests.includes('software')) {
        if(scores.hasOwnProperty('CS')) scores['CS'] += 3;
        if(scores.hasOwnProperty('SW')) scores['SW'] += 3;
        // Add bonus for any department with "computer" or "software" in name
        deptCodes.forEach(code => {
            const name = deptNames[code].toLowerCase();
            if(name.includes('computer') || name.includes('software')) scores[code] += 2;
        });
    }
    
    if(interests.includes('circuit') || interests.includes('electr') || interests.includes('power') || interests.includes('hardware')) {
        if(scores.hasOwnProperty('EE')) scores['EE'] += 4;
        deptCodes.forEach(code => {
            const name = deptNames[code].toLowerCase();
            if(name.includes('electr') || name.includes('power')) scores[code] += 3;
        });
    }
    
    if(interests.includes('business') || interests.includes('management') || interests.includes('analysis')) {
        if(scores.hasOwnProperty('IS')) scores['IS'] += 4;
        deptCodes.forEach(code => {
            const name = deptNames[code].toLowerCase();
            if(name.includes('business') || name.includes('information')) scores[code] += 3;
        });
    }
    
    if(interests.includes('web') || interests.includes('app') || interests.includes('mobile')) {
        deptCodes.forEach(code => {
            const name = deptNames[code].toLowerCase();
            if(name.includes('software') || name.includes('computer')) scores[code] += 2;
        });
    }
    
    if(interests.includes('ai') || interests.includes('data') || interests.includes('machine') || interests.includes('intelligence')) {
        if(scores.hasOwnProperty('CS')) scores['CS'] += 3;
        deptCodes.forEach(code => {
            const name = deptNames[code].toLowerCase();
            if(name.includes('computer') || name.includes('data')) scores[code] += 2;
        });
    }
    
    if(interests.includes('network') || interests.includes('cloud') || interests.includes('infrastructure')) {
        deptCodes.forEach(code => {
            const name = deptNames[code].toLowerCase();
            if(name.includes('information') || name.includes('computer')) scores[code] += 2;
        });
    }
    
    if(interests.includes('design') || interests.includes('create') || interests.includes('innovate')) {
        deptCodes.forEach(code => {
            const name = deptNames[code].toLowerCase();
            if(name.includes('engineer') || name.includes('software')) scores[code] += 2;
        });
    }
    
    // SKILL-BASED SCORING
    if(skills.includes('math') || skills.includes('logic') || skills.includes('algorithm')) {
        if(scores.hasOwnProperty('CS')) scores['CS'] += 2;
        if(scores.hasOwnProperty('EE')) scores['EE'] += 1;
        deptCodes.forEach(code => {
            const name = deptNames[code].toLowerCase();
            if(name.includes('engineer')) scores[code] += 1;
        });
    }
    
    if(skills.includes('program') || skills.includes('code') || skills.includes('develop')) {
        if(scores.hasOwnProperty('CS')) scores['CS'] += 2;
        if(scores.hasOwnProperty('SW')) scores['SW'] += 2;
        deptCodes.forEach(code => {
            const name = deptNames[code].toLowerCase();
            if(name.includes('computer') || name.includes('software')) scores[code] += 2;
        });
    }
    
    if(skills.includes('communicat') || skills.includes('business') || skills.includes('lead')) {
        if(scores.hasOwnProperty('IS')) scores['IS'] += 2;
        deptCodes.forEach(code => {
            const name = deptNames[code].toLowerCase();
            if(name.includes('business') || name.includes('information') || name.includes('management')) scores[code] += 2;
        });
    }
    
    if(skills.includes('creativ') || skills.includes('design')) {
        if(scores.hasOwnProperty('SW')) scores['SW'] += 2;
    }
    
    if(skills.includes('analysis') || skills.includes('research')) {
        deptCodes.forEach(code => {
            scores[code] += 1; // Bonus for analytical skills
        });
    }
    
    // GPA BONUS
    if(gpa >= 3.5) {
        deptCodes.forEach(code => {
            scores[code] += 1;
        });
    }
    
    // Sort departments by score
    const sorted = Object.entries(scores).sort(([,a], [,b]) => b - a);
    const top = sorted[0];
    const maxScore = Math.max(...Object.values(scores), 1);
    const pct = Math.min(100, Math.round((top[1] / maxScore) * 100));
    
    // Display results
    let resultHTML = `
        <div class="recommendation-result">
            <h2>Your Recommended Department</h2>
            <p class="match-percentage">${pct}% Match</p>
            <h3>${deptNames[top[0]]} (${top[0]})</h3>
            <p>Based on your interests and skills, we recommend you explore <strong>${deptNames[top[0]]}</strong>.</p>
            <a href="departments.php?dept=${top[0]}" class="btn btn-solid-success" style="margin-top: 1rem;">
                <i class="fas fa-eye"></i> Learn More About ${deptNames[top[0]]}
            </a>
        </div>
        
        <h3 style="margin-top: 2rem;">Other Department Matches:</h3>
        <div class="card-grid">
    `;
    
    sorted.slice(1).forEach(([code, score]) => {
        const matchPct = Math.min(100, Math.round((score / maxScore) * 100));
        resultHTML += `
            <div class="card">
                <h4>${deptNames[code]}</h4>
                <p style="font-size: 1.5rem; font-weight: 700; color: #3b82f6;">${matchPct}% Match</p>
                <p style="font-size: 0.85rem; color: #6b7280;">Department Code: ${code}</p>
                <a href="departments.php?dept=${code}" class="btn btn-outline btn-sm">View Details</a>
            </div>
        `;
    });
    
    resultHTML += '</div>';
    
    document.getElementById('recommendationResult').innerHTML = resultHTML;
    document.getElementById('recommendationResult').scrollIntoView({ behavior: 'smooth' });
});
</script>

<?php include 'includes/footer.php'; ?>