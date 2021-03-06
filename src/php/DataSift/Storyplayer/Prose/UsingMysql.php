<?php

/**
 * Copyright (c) 2011-present Mediasift Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Libraries
 * @package   Storyplayer/Prose
 * @author    Stuart Herbert <stuart.herbert@datasift.com>
 * @copyright 2011-present Mediasift Ltd www.datasift.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://datasift.github.io/storyplayer
 */

namespace DataSift\Storyplayer\Prose;
use mysqli;

/**
 * do things to a database
 *
 * great for managing test data
 *
 * @category  Libraries
 * @package   Storyplayer/Prose
 * @author    Stuart Herbert <stuart.herbert@datasift.com>
 * @copyright 2011-present Mediasift Ltd www.datasift.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://datasift.github.io/storyplayer
 */
class UsingMysql extends Prose
{
	public function __construct($st, $args)
	{
		parent::__construct($st, $args);

		if (!isset($args[0])) {
			throw new E5xx_ActionFailed(__METHOD__, "param 1 must be the host of the MySQL server");
		}
		if (!isset($args[1])) {
			throw new E5xx_ActionFailed(__METHOD__, "param 2 must be the MySQL user to use");
		}
		if (!isset($args[2])) {
			throw new E5xx_ActionFailed(__METHOD__, "param 2 must be the password for the MySQL user");
		}
	}

	public function query($sql)
	{
		// shorthand
		$st = $this->st;

		// what are we doing?
		$log = $st->startAction(["run SQL against '{$this->args[0]}':", $sql]);

		// connect
		$conn = new mysqli($this->args[0], $this->args[1], $this->args[2]);
		if ($conn->connect_errno) {
			$log->endAction('unable to connect to database :( - ' . $conn->connect_error);
			throw new E5xx_ActionFailed(__METHOD__);
		}

		// switch database
		if (isset($this->args[2])) {
			if (!$conn->select_db($this->args[3])) {
				$log->endAction("unable to switch to database '{$this->args[2]}' - " . $conn->error);
				throw new E5xx_ActionFailed(__METHOD__);
			}
		}

		// run the SQL
		$result = $conn->query($sql);

		// what happened?
		if (!$result) {
			$log->endAction("failed to run query");
			throw new E5xx_ActionFailed(__METHOD__, "query failed - " . $conn->error);
		}

		// success
		$log->endAction();
		return $result;
	}
}
