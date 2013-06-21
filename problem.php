<?php
$problem_id = strtoupper($_GET["problem_id"]);
include("icat.php");
$db = init_db();

?>
<!doctype html public "-//w3c//dtd html 4.0 transitional//en">
<html>
<head>
<title>iCAT -- problems</title>

<link rel="stylesheet" type="text/css" href="feed.css" />
<link rel="stylesheet" type="text/css" href="style.css" />
<style type="text/css">
h1 { text-align: center; margin: 0px; }

div#feed_container { display: table; table-layout: fixed; width: 100%; }
div#entries_feed_container, div#edit_activity_feed_container,
div#submission_feed_container { display: table-cell; }

div#problem_statistics {
    display: inline-block;
    width: 40%;
}

div#activity_container {
    background: #ddd;
    width: 55%;
    height: 200px;
    display: inline-block;
}

</style>

<script type="text/javascript" src="katalyze/web/jquery-1.6.1.js"></script>
<script type="text/javascript" src="feed.js"></script>
<script type="text/javascript" src="misc.js"></script>
<script type="text/javascript" src="flot/jquery.flot.min.js"></script>
<script type="text/javascript" src="flot/jquery.flot.resize.min.js"></script>
<script type="text/javascript" src="flot/jquery.flot.navigate.min.js"></script>
<script type="text/javascript" src="activity.js"></script>
<script type="text/javascript">

// Set up the feeds.
$(document).ready(function() {
    var problem_id = '<?php echo $problem_id; ?>';

    new feed("#edit_activity_feed_container", {
        name: "Edit activity",
        table: 'edit_activity',
        conditions: 'problem_id = "' + problem_id + '" and valid != 0',
    });

    new feed("#entries_feed_container", {
        name: 'Katalyze events',
        conditions: 'text regexp "#p' + problem_id + '[[:>:]]"'
    });

    new feed("#submission_feed_container", {
        name: "Submissions",
        table: 'submissions',
        conditions: 'problem_id = "' + problem_id + '"',
    });

    new ActivityPlot($("#activity_container"), '', problem_id);
});

</script>
</head>
<body>

<?php navigation_container(); ?>

<h1>Problem <?php echo $problem_id; ?> &mdash; <?php echo $PROBLEM_ID_TO_NAME[$problem_id]; ?> </h1>

<div id="statistics_activity_container">
<div id='problem_statistics'>
Statistics about the problem:
<ul>
        <?php
        #########################################
        $result = mysql_query("select count(distinct team_id) as num_solutions, "
            . " avg(distinct contest_time) as avg_time_to_soln, min(contest_time) as first_solution_time "
            . " from submissions where problem_id = '$problem_id' and result = 'AC'",
            $db);
        $row = mysql_fetch_assoc($result);
        $num_solutions = $row['num_solutions'];
        $avg_time_to_soln = intval($row['avg_time_to_soln']);
        $first_solution_time = $row['first_solution_time'];

        #########################################
        $result = mysql_query(
            "select avg(c) as avg_num_incorrect_submissions from "
            . " (select count(*) - 1 as c from submissions where team_id in "
            . " (select distinct(team_id) from submissions where problem_id = '$problem_id' and result = 'AC') "
            . " and problem_id = '$problem_id' group by team_id) as arbitrary_table_name",
           $db); 
        $row = mysql_fetch_assoc($result);
        $avg_num_incorrect_submissions = sprintf("%0.2f", $row['avg_num_incorrect_submissions']);

        #########################################
        $result = mysql_query(
            "select min(contest_time) as first_submission_time, count(*) as count "
            . " from submissions where problem_id = '$problem_id'", $db);
        $row = mysql_fetch_assoc($result);
        $first_submission_time = $row["first_submission_time"];
        $num_submissions = $row["count"];

        
        #########################################
        $result = mysql_query("select distinct team_id as team_id from submissions "
            . " where problem_id = '$problem_id' and contest_time = $first_submission_time order by team_id", $db);
        $first_teams_to_submit = array();
        while ($row = mysql_fetch_assoc($result)) {
            $first_teams_to_submit[] = sprintf("<a href='team_feed.php?team_id=%d'>%d</a>", $row['team_id'], $row['team_id']);
        }
        $first_teams_to_submit = $first_teams_to_submit ? sprintf("(Team %s)", implode(", ", $first_teams_to_submit)) : "";

        #########################################
        $result = mysql_query("select distinct team_id as team_id from submissions "
            . " where problem_id = '$problem_id' and result = 'AC' and contest_time = $first_solution_time order by team_id", $db);
        $first_teams_to_solve = array();
        while ($row = mysql_fetch_assoc($result)) {
            $first_teams_to_solve[] = sprintf("<a href='team_feed.php?team_id=%d'>%d</a>", $row['team_id'], $row['team_id']);
        }
        $first_teams_to_solve = $first_teams_to_solve ? sprintf("(Team %s)", implode(", ", $first_teams_to_solve)) : "";

        #########################################
        $submissions_by_language = array();
        $solutions_by_language = array();
        $result = mysql_query("select count(*) as cnt, lang_id from submissions where problem_id = '$problem_id' group by lang_id", $db);
        while ($row = mysql_fetch_assoc($result)) {
            $submissions_by_language[] = sprintf("%s: %d", $row["lang_id"], $row["cnt"]);
        }
        $result = mysql_query("select count(*) as cnt, lang_id from submissions where problem_id = '$problem_id' and result = 'AC' group by lang_id", $db);
        while ($row = mysql_fetch_assoc($result)) {
            $solutions_by_language[] = sprintf("%s: %d", $row["lang_id"], $row["cnt"]);
        }
        $submissions_by_language = implode(", ", $submissions_by_language);
        $solutions_by_language = implode(", ", $solutions_by_language);

        #########################################
       #$result = mysql_query("select count(*) as num_started_problem from "
       #    . " (select *, count(*) as c from edit_activity "
       #    . " where problem_id = '$problem_id' and valid != 0 group by team_id having c > 1) as arbitrary_table_name",
       #    $db);
       #$row = mysql_fetch_assoc($result);
       #$num_started_problem = $row["num_started_problem"];

        #########################################
        $result = mysql_query("select count(*) as count_one_edit from "
            . " (select *, count(*) as c from edit_activity "
            . " where problem_id = '$problem_id' and valid != 0 group by team_id having c = 1) as arbitrary_table_name",
            $db);
        $row = mysql_fetch_assoc($result);
        $count_one_edit = $row["count_one_edit"];

        #########################################
        $result = mysql_query("select count(*) as count_two_plus_edits from "
            . " (select *, count(*) as c from edit_activity "
            . " where problem_id = '$problem_id' and valid != 0 group by team_id having c > 1) as arbitrary_table_name",
            $db);
        $row = mysql_fetch_assoc($result);
        $count_two_plus_edits = $row["count_two_plus_edits"];

        #########################################
        $result = mysql_query("select count(distinct team_id) as num_submitted_problem "
            . " from submissions where problem_id = '$problem_id'",
            $db);
        $row = mysql_fetch_assoc($result);
        $num_submitted_problem = $row["num_submitted_problem"];

        ?>
    <li>Teams solved: <?php echo $num_solutions; ?> /
         Teams submitted but not solved: <?php echo $num_submitted_problem - $num_solutions; ?> /
         Total submissions: <?php echo $num_submissions; ?>
    <li># Solutions by language: <?php echo $solutions_by_language; ?>
    <li># Submissions by language: <?php echo $submissions_by_language; ?>
    <li>Avg. time to solution: <?php echo $avg_time_to_soln; ?> min.
    <li>Avg. # incorrect submissions before accepted: <?php echo $avg_num_incorrect_submissions; ?>
    <li>First submission / solution: <?php printf("%d min. %s / %d min. %s", 
        $first_submission_time, $first_teams_to_submit, $first_solution_time, $first_teams_to_solve); ?>
    <li># teams with 1 edit: <?php echo $count_one_edit; ?>
    <li># teams with 2+ edits: <?php echo $count_two_plus_edits; ?>


        <?php
            /*
    <li>Teams that solved this problem (in order of solution):
            do {
                printf("<a href='team_feed.php?team_id=%d'>%d</a>, ", $row['team_id'], $row['team_id']);
            } while ($row = mysql_fetch_assoc($result));
            */
        ?>
</ul>

</div>
<div id='activity_container'></div>
</div>

<div id='feed_container'>
<div id='entries_feed_container'></div>
<div id='edit_activity_feed_container'></div>
<div id='submission_feed_container'></div>
</div>

<?php add_entry_container(); ?>

</body>
</html>
