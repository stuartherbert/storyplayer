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
 * @package   Storyplayer/OsLib
 * @author    Stuart Herbert <stuart.herbert@datasift.com>
 * @copyright 2011-present Mediasift Ltd www.datasift.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://datasift.github.io/storyplayer
 */

namespace DataSift\Storyplayer\OsLib;

use DataSift\Stone\ObjectLib\BaseObject;
use DataSift\Storyplayer\HostLib\SupportedHost;

/**
 * get information about vagrant
 *
 * @category  Libraries
 * @package   Storyplayer/Prose
 * @author    Stuart Herbert <stuart.herbert@datasift.com>
 * @copyright 2011-present Mediasift Ltd www.datasift.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://datasift.github.io/storyplayer
 */

abstract class Base_Centos5 extends Base_Unix
{
	/**
	 *
	 * @param  HostDetails $hostDetails
	 * @param  SupportedHost $host
	 * @return string
	 */
	public function determineIpAddress($hostDetails, SupportedHost $host)
	{
		// shorthand
		$st = $this->st;

		// what are we doing?
		$log = $st->startAction("query " . basename(__CLASS__) . " for IP address");

		// how do we do this?
		foreach (array('eth1', 'eth0') as $iface) {
			$command = "/sbin/ifconfig {$iface} | grep 'inet addr' | awk -F : '{print \\\$2}' | awk '{print \\\$1}'";
			$result = $host->runCommandViaHostManager($hostDetails, $command);

			if ($result->didCommandSucceed()) {
				$lines = explode("\n", $result->output);
				$ipAddress = trim($lines[0]);
				$log->endAction("IP address is '{$ipAddress}'");
				return $ipAddress;
			}
		}

		// if we get here, we do not know what the IP address is
		$msg = "could not determine IP address";
		$log->endAction($msg);
		throw new E5xx_ActionFailed(__METHOD__, $msg);
	}

	/**
	 *
	 * @param  HostDetails $hostDetails
	 * @param  string $packageName
	 * @return BaseObject
	 */
	public function getInstalledPackageDetails($hostDetails, $packageName)
	{
		// shorthand
		$st = $this->st;

		// what are we doing?
		$log = $st->startAction("get details for package '{$packageName}' installed in host '{$hostDetails->name}'");

		// get the details
		$command   = "sudo yum list installed {$packageName} | grep '{$packageName}' | awk '{print \\\$1,\\\$2,\\\$3}'";
		$result    = $this->runCommand($hostDetails, $command);

		// any luck?
		if ($result->didCommandFail()) {
			$log->endAction("could not get details ... package not installed?");
			return new BaseObject();
		}

		// study the output
		$parts = explode(' ', $result->output);
		if (count($parts) < 3) {
			$log->endAction("could not get details ... package not installed?");
			return new BaseObject();
		}
		if (strtolower($parts[0]) == 'error:') {
			$log->endAction("could not get details ... package not installed?");
			return new BaseObject();
		}

		// we have some information to return
		$return = new BaseObject();
		$return->name = $parts[0];
		$return->version = $parts[1];
		$return->repo = $parts[2];

		// all done
		$log->endAction();
		return $return;
	}
}