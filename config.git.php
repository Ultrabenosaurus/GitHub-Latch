<?php

// your GitHub Webhook secret
$SECRET = "1234561234";

// absolute path to you logs dir
$LOG_DIR = "/var/www/logs/git/" . date("Y") . "/" . date("m") . "/" . date("d") . "/";

// the name of your log file
$LOG_FILE = date("His");

// absolute paths to your checked-out directories
$PULL_DIRS = array(
    "/var/www/my_dir",
    "/var/www/dev",
    "/var/www/stage",
    "/var/www/live"
);

/**
 * map of commands to run
 *
 * directory =>
 *      event =>
 *          branch =>
 *              username => commands
 *
 * directory        as in `$PULL_DIRS`
 * event            as in https://developer.github.com/webhooks/#events
 * branch           name of branch to run commands on; 'self' will match the directory's current branch
 * username         GitHub username that appears under 'committer' in the payload's first commit object; 'default' will run on any user
 * commands         an array of Git commands (including Git aliases)
 *
 * `git fetch` will be run on any directory in `$PULL_DIRS` that has no matching rulle in this map
 *
 */
$DIR_COMMANDS = array(
    "/var/www/my_dir" => array(
        "push" => array(
            "self" => array(
                "ultrabenosaurs" => array("r", "pull"),
                "default" => array("fetch")
            )
        )
        "merge" => array(
            "master" => array(
                "ultrabenosaurs" => array("r", "fetch", "merge"),
                "default" => array("fetch")
            ),
            "self" => array(
                "ultrabenosaurs" => array("r", "pull"),
                "default" => array("fetch")
            )
        )
    )
);

// wrap all variables for passing to constructor
$CONFIG = array(
    "SECRET" => $SECRET,
    "LOG_DIR" => $LOG_DIR,
    "LOG_FILE" => $LOG_FILE,
    "PULL_DIRS" => $PULL_DIRS,
    "DIR_COMMANDS" => $DIR_COMMANDS
);

?>