<?php

class Git
{
	private $SECRET;
	private $LOG_DIR;
	private $LOG_FILE;
	private $PULL_DIRS;
	private $DIR_COUNT;
	private $DIR_COMMANDS;
	private $RAW_POST;
	private $HEADERS;
	private $VALID;
	private $PAYLOAD;
	private $EVENT;

	public function __construct( $_config )
	{
		$this->SECRET = $_config['SECRET'];
		$this->LOG_DIR = $_config['LOG_DIR'];
		$this->LOG_FILE = $_config['LOG_FILE'];
		$this->PULL_DIRS = $_config['PULL_DIRS'];
		$this->DIR_COUNT = count($this->PULL_DIRS);
		$this->DIR_COMMANDS = $_config['DIR_COMMANDS'];

		$this->RAW_POST = $HTTP_RAW_POST_DATA;
		$this->HEADERS = $this->parseHeaders();
		$this->VALID = $this->validateHash();
		$this->PAYLOAD = json_decode($this->RAW_POST);

		$this->EVENT = $this->getEvent();
	}

	public function processDirs( $_return = false, $_log = true )
	{
		$shellOutput = array();
		for($i = 0; $i < $DIR_COUNT; $i++)
		{
			$branch = array();
			exec("cd " . $PULL_DIRS[$i] . " && git rev-parse --abbrev-ref HEAD", $branch);
			$result = $this->processDir( $PULL_DIRS[$i], $branch[0] );
			$shellOutput[$PULL_DIRS[$i]] = array('branch' => $branch[0], 'commands' => $result["commands"], 'result' => $result["output"]);
		}
		if( $_log ) $this->writeLog( $shellOutput );
	}

	private function processDir( $_dir, $_branch )
	{
		$commands = $this->getCommand( $_dir, $_branch );
		$comOut = array();
		exec("cd " . $_dir . $commands['command'] . $_branch, $comOut);

		return array("commands" => $commands, "output" => $comOut);
	}

	private function parseHeaders()
	{
		$HEADERS = array();
		foreach($_SERVER as $key => $value)
		{
			if (substr($key, 0, 5) <> 'HTTP_')
			{
				continue;
			}
			$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
			$HEADERS[$header] = $value;
		}
		return $HEADERS;
	}

	private function validateHash()
	{
		$HASH = hash_hmac("sha1", $HTTP_RAW_POST_DATA, $SECRET);
		return ("sha1=".$HASH === $this->HEADERS['X-Hub-Signature']);
	}

	private function getEvent()
	{
		$evt = array();
		$message = explode("\n", $this->PAYLOAD->commits[0]->message);
		$refs = explode( "/", $this->PAYLOAD->refs );
		array_pop( $refs );
		array_pop( $refs );
		if( preg_match( "/^Merge[^\/]+\/(.*)/", $message[0], $matches ) === 1 )
		{
			$evt["event"] = "merge";
			$evt["details"] = array(
				"author" => $this->PAYLOAD->commits[0]->committer->username,
				"branch" => array(
					"into" => implode( "/", $refs ),
					"from" => $matches[1]
			);
		}
		else
		{
			$evt["event"] = $this->HEADERS['X-Github-Event'];
			$evt["details"] = array(
				"author" => $this->PAYLOAD->commits[0]->committer->username,
				"branch" => implode( "/", $refs )
			);
		}
		return $evt;
	}

	private function getCommand( $_dir, $_branch )
	{
		$commands = array( "fetch" );
		$out = array( "tasks" => array(), "command" => '');
		$target_branch = ( ( isset( $this->EVENT['details']['branch']['into'] ) ) ? $this->EVENT['details']['branch']['into'] : $this->EVENT['details']['branch'] );

		if( isset( $this->DIR_COMMANDS[$_dir] ) ) // commands for current directory
			if( isset( $this->DIR_COMMANDS[$_dir][$this->EVENT['event']] ) ) // commands for this event for the current directory
				if( isset( $this->DIR_COMMANDS[$_dir][$this->EVENT['event']][$target_branch] ) ) // commands for this branch for this event in the current directory
					if( isset( $this->DIR_COMMANDS[$_dir][$this->EVENT['event']][$target_branch][$this->EVENT['event']['details']['author']] ) )
						$commands = $this->DIR_COMMANDS[$_dir][$this->EVENT['event']][$target_branch][$this->EVENT['event']['details']['author']];
					elseif( isset( $this->DIR_COMMANDS[$_dir][$this->EVENT['event']][$target_branch]['default'] ) )
						$commands = $this->DIR_COMMANDS[$_dir][$this->EVENT['event']][$target_branch]['default'];

		$out['tasks'] = $commands;
		for( $i = count( $commands ); $i > 0; $i-- )
		{
			if( "merge" == $commands[$i] ) $commands[$i] .= " origin/" . $target_branch;
			if( $i == 1 )
				$out['command'] .= 'git ' . $commands[$i];
			else
				$out['command'] .= 'git ' . $commands[$i] . ' && ';
		}
		return $out;
	}

	private function writeLog( $_shell )
	{
		$log['headers'] = $this->HEADERS;
		$log['payload'] = $this->PAYLOAD;
		$log['hash'] = ( ( $this->VALID ) ? "validated" : "failed" );
		$log['commands'] = $_shell;
		$this->checkLogDir();
		file_put_contents($this->LOG_DIR . $this->LOG_FILE, json_encode($log));
	}

	private function checkLogDir()
	{
		if(!is_dir($this->LOG_DIR))
		{
			$dirs = explode("/", $this->LOG_DIR);
			$path = $dirs[0];
			$c = count($dirs);
			for ($i=0; $i < $c; $i++) {
				if(!is_dir($path)) mkdir($path);
				$path .= "/" . $dirs[$i+1];
			}
			unset($dirs);
			unset($path);
			unset($c);
		}
	}
}

?>