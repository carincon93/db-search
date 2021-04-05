<?php
    // Freelancer - contest relaltionship - saveContestApplication()
    $freelancerId       = isset($_REQUEST["freelancer_id"]) ? $_REQUEST["freelancer_id"] : null;
    $contestId          = isset($_REQUEST["contest_id"]) ? $_REQUEST["contest_id"] : null;

    // Filters
    $jobTitleRequest           = isset($_REQUEST["job_title"]) ? $_REQUEST["job_title"] : null;
    $skillRequest              = isset($_REQUEST["skill"]) ? $_REQUEST["skill"] : null;
    $qtyMonthsRequest          = isset($_REQUEST["qty_months"]) ? $_REQUEST["qty_months"] : null;
    $filterOptionRequest       = isset($_REQUEST["filter_option"]) ? $_REQUEST["filter_option"] : null;

    // To get info
    $freelancers        = [];
    $contestFiltered    = null;
    $freelancersContestFiltered = [];
    $jobTitles = [];

    // Connect to database
    $host       = "localhost";
    $dbname     = "freelancer";
    $username   = "root";
    $password   = "";

    $cnx = new PDO("mysql:host=$host;dbname=$dbname",$username,$password);

    function getAllFreelancers($cnx) {
        $sql = "SELECT * FROM users WHERE role = 1";
        
        $q = $cnx->prepare($sql);

        $result = $q->execute();

        return $q->fetchAll();
    }

    function getContestById($contestId, $cnx) {
        $sql = "SELECT * FROM contests WHERE id = $contestId";
        
        $q = $cnx->prepare($sql);

        $result = $q->execute();

        return $q->fetch();
    }

    function getFreelancersByContest($contestId, $cnx) {
        $sql = "SELECT users.id, users.name, freelancer_cv.job_title, freelancer_cv.qty_months, GROUP_CONCAT(skills.name) as skill_name FROM users 
            JOIN freelancer_cv ON users.id = freelancer_cv.freelancer_id 
            JOIN contest_applications ON users.id = contest_applications.freelancer_id 
            JOIN freelancer_cv_skill ON freelancer_cv.id = freelancer_cv_skill.freelancer_cv_id
            JOIN skills ON freelancer_cv_skill.skill_id = skills.id
            WHERE contest_applications.contest_id = $contestId GROUP BY users.id";
        
        $q = $cnx->prepare($sql);

        $result = $q->execute();

        return $q->fetchAll();
    }

    function getAllContests($cnx) {
        $sql    = "SELECT id FROM contests";
        
        $q      = $cnx->prepare($sql);

        $result = $q->execute();

        return $q->fetchAll();
    }

    function getJobTitles($cnx) {
        $sql    = "SELECT freelancer_cv.job_title as name FROM freelancer_cv";
        
        $q      = $cnx->prepare($sql);

        $result = $q->execute();

        return $q->fetchAll();
    }

    function getSkills($cnx) {
        $sql    = "SELECT skills.name as name FROM skills";
        
        $q      = $cnx->prepare($sql);

        $result = $q->execute();

        return $q->fetchAll();
    }

    function saveContestApplication($freelancerId, $contestId, $cnx) {
        $sql    = "INSERT INTO contest_applications (freelancer_id, contest_id) VALUES ($freelancerId, $contestId)";
        
        $q      = $cnx->prepare($sql);

        $resultSaveContest = $q->execute();
    }

    $freelancers    = getAllFreelancers($cnx);
    $contests       = getAllContests($cnx);
    $jobTitles      = getJobTitles($cnx);
    $skills         = getSkills($cnx);

    if ($freelancerId && $contestId) {
        saveContestApplication($freelancerId, $contestId, $cnx);
    }

    if (!empty($contestId)) {
        $contestFiltered = getContestById($contestId, $cnx);
        $freelancersContestFiltered = getFreelancersByContest($contestId, $cnx);

        // OR

        // 2 && 1empty($jobTitleRequest) && !empty($qtyMonthsRequest) && !empty($skillRequest)
        if ($filterOptionRequest == 1) {
            $condition = !empty($jobTitleRequest) ? "AND freelancer_cv.job_title = '$jobTitleRequest'" : null;
            $condition.= !empty($qtyMonthsRequest) && !empty($condition) ? " OR contest_applications.contest_id = $contestId AND freelancer_cv.qty_months >= $qtyMonthsRequest" : (!empty($qtyMonthsRequest) && empty($condition) ? " AND freelancer_cv.qty_months >= $qtyMonthsRequest" : null);
            $condition.= !empty($skillRequest) && !empty($condition) ? " OR contest_applications.contest_id = $contestId AND skills.name = '$skillRequest'" : (!empty($skillRequest) && empty($condition) ? " AND skills.name = '$skillRequest'" : null);
        } elseif ($filterOptionRequest == 2) {
            $condition = "AND freelancer_cv.job_title = '$jobTitleRequest' AND freelancer_cv.qty_months >= $qtyMonthsRequest AND skills.name = '$skillRequest'";
        }

        if (!empty($condition)) {
            $sql = "SELECT users.id, users.name, freelancer_cv.job_title, freelancer_cv.qty_months, GROUP_CONCAT(skills.name) as skill_name FROM users 
            JOIN freelancer_cv ON users.id = freelancer_cv.freelancer_id 
            JOIN contest_applications ON users.id = contest_applications.freelancer_id 
            JOIN freelancer_cv_skill ON freelancer_cv.id = freelancer_cv_skill.freelancer_cv_id
            JOIN skills ON freelancer_cv_skill.skill_id = skills.id
            WHERE contest_applications.contest_id = $contestId
            $condition GROUP BY users.id";

            $q      = $cnx->prepare($sql);

            $result = $q->execute();

            $freelancersContestFiltered = $q->fetchAll();
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contest application</title>
    <link rel="stylesheet" href="./assets/app.css">
</head>
<body>
    <div class="flex min-height-100vh">
        <div class="sidebar">
            <header>
                <h2 class="text-center">FREE<span class="thin">LANCER</span></h2>
                <!-- <figure>
                    <img src="" alt="">
                </figure> -->
            </header>
            <div>
                <a href="./create-contest.php" class="sidebar-link flex items-center bg-purple">
                    🥇
                    <span class="ml-10">Create contest</span>
                </a>
            </div>

            <p class="text-center m-40-0 text-gray">All notifications</p>

            <?php foreach ($contests as $contest): ?>
                <div>
                    <a href="./contest-application.php?contest_id=<?= $contest['id'] ?>" class="sidebar-link flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="nav-icon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>Hey <span class="strong">free</span>lancer! We have a new contest here </span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="main">
            <div class="container">
                <?php if($contestFiltered): ?>
                    <div class="card">
                        <span>#Contest00<?= $contestFiltered['id'] ?></span>
                        <p><?= $contestFiltered['title'] ?></p>
                        <p class="mt-10"><?= $contestFiltered['description'] ?></p>
                        <p class="mt-10">Value: <?= $contestFiltered['value'] ?> USD 💵</p>

                        <form action="./contest-application.php" method="POST">
                            <input type="hidden" name="contest_id" value="<?= $contestFiltered['id'] ?>">
                            <div>
                                <div>
                                    <label for="freelancer_id">Work with a <span class="strong">free</span>lancer friend 😎</label>
                                </div>
                                <select name="freelancer_id" id="freelancer_id" class="form-control mt-10" required>
                                    <option value="">Select a freelancer from your friends</option>
                                    <?php foreach ($freelancers as $freelancer): ?>
                                        <option value="<?= $freelancer['id'] ?>" <?= ("$freelancerId" == $freelancer['id']) ? 'selected' : '' ?> ><?= $freelancer['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mt-10">
                                <button type="submit" class="btn-primary">Apply</button>
                            </div>
                        </form>
                    </div>

                    <hr class="m-85">

                    <h1>Freelancers</h1>
                    <div>
                        <h6>Filters</h6>
                        <form action="./contest-application.php" method="GET">
                            <input type="hidden" name="contest_id" value="<?= $contestFiltered['id'] ?>">
                            
                            <div class="flex items-center justify-between">
                                <div>
                                    <div>
                                        <label for="job_title">Job title</label>
                                    </div>
                                    <select name="job_title" id="job_title" class="form-control mt-10">
                                        <option value="">Select a job title</option>
                                        <?php foreach ($jobTitles as $jobTitle): ?>
                                            <option value="<?= $jobTitle['name'] ?>" <?= $jobTitle['name'] === $jobTitleRequest ? 'selected' : '' ?> ><?= $jobTitle['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <div>
                                        <label for="skill">Skills</label>
                                    </div>
                                    <select name="skill" id="skill" class="form-control mt-10">
                                        <option value="">Select a skill</option>
                                        <?php foreach ($skills as $skill): ?>
                                            <option value="<?= $skill['name'] ?>" <?= $skill['name'] === $skillRequest ? 'selected' : '' ?> ><?= $skill['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <div class="mt-10">
                                        <label for="qty_months">Experience</label>
                                    </div>
                                    <input type="number" min="0" name="qty_months" id="qty_months" value="<?= $qtyMonthsRequest ?>" class="form-control mt-10" />
                                </div>

                                <div>
                                    <div class="mt-10">
                                        <label for="filter_option">Filter options</label>
                                    </div>
                                    <select name="filter_option" id="filter_option" class="form-control mt-10" required>
                                        <option value="">Select an option</option>
                                        <option value="1" <?= 1 == $filterOptionRequest ? 'selected' : '' ?> >Search any</option>
                                        <option value="2" <?= 2 == $filterOptionRequest ? 'selected' : '' ?> >Search all</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-10">
                                <button type="submit" class="btn-primary">Filter</button>
                                <a href="./contest-application.php?contest_id=<?= $contestFiltered['id'] ?>" class="btn">
                                    <span>Clear</span>
                                </a>
                            </div>
                        </form>
                    </div>
                    <hr class="m-85">
                    <h4>Results: <?= count($freelancersContestFiltered) ?></h4>
                    <?php if (count($freelancersContestFiltered) > 0): ?>
                        <div class="flex justify-between mt-10">
                            <?php foreach ($freelancersContestFiltered as $freelancerContest): ?>
                                <div class="card p-8 mt-10" style="flex: 1 0 33.333333%;">
                                    <h2><?= $freelancerContest['name'] ?></h2>
                                    <div>
                                        <span>Job title: <?= $freelancerContest['job_title'] ?></span>
                                    </div>
                                    <div class="mt-10">
                                        <span>Experience: <?= $freelancerContest['qty_months'] ?> months</span>
                                    </div>
                                    <div class="mt-10">
                                        <span>Skill: <?= $freelancerContest['skill_name'] ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No data recorded</p>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="card">
                        <h1 class="text-center">Nothing to see here</h1>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
