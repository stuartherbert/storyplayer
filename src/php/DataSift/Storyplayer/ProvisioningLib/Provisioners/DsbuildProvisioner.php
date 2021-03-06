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
 * @package   Storyplayer/ProvisioningLib
 * @author    Stuart Herbert <stuart.herbert@datasift.com>
 * @copyright 2011-present Mediasift Ltd www.datasift.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://datasift.github.io/storyplayer
 */

namespace DataSift\Storyplayer\ProvisioningLib\Provisioners;

use DataSift\Stone\ObjectLib\BaseObject;
use DataSift\Storyplayer\CommandLib\CommandResult;
use DataSift\Storyplayer\CommandLib\CommandRunner;
use DataSift\Storyplayer\PlayerLib\StoryTeller;
use DataSift\Storyplayer\Prose\E5xx_ActionFailed;
use DataSift\Storyplayer\ProvisioningLib\ProvisioningDefinition;

/**
 * support for provisioning via dsbuild
 *
 * @category  Libraries
 * @package   Storyplayer/ProvisioningLib
 * @author    Stuart Herbert <stuart.herbert@datasift.com>
 * @copyright 2011-present Mediasift Ltd www.datasift.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://datasift.github.io/storyplayer
 */
class DsbuildProvisioner extends Provisioner
{
	public function __construct(StoryTeller $st)
	{
		// remember for the future
		$this->st = $st;
	}

	public function buildDefinitionFor($env)
	{
		// our return value
		$provDef = new ProvisioningDefinition;

		// shorthand
		$st = $this->st;

		// what are we doing?
		$log = $st->startAction("build dsbuild provisioning definition");

		// add in each machine in the environment
		foreach ($env->details->machines as $name => $machine) {
			$st->usingProvisioningDefinition($provDef)->addHost($name);

			foreach ($machine->roles as $role) {
				$st->usingProvisioningDefinition($provDef)->addRole($role)->toHost($name);
			}

			if (isset($machine->params)) {
				$params = [];
				foreach ($machine->params as $paramName => $paramValue) {
					$params[$paramName]  = $st->fromTestEnvironment()->getSetting('hosts.' . $name . '.params.'.$paramName);
				}
				if (count($params)) {
					$st->usingProvisioningDefinition($provDef)->addParams($params)->toHost($name);
				}
			}
		}

		// all done
		return $provDef;
	}

	public function provisionHosts(ProvisioningDefinition $hosts)
	{
		// shorthand
		$st = $this->st;

		// what are we doing?
		$log = $st->startAction("use dsbuild to provision host(s)");

		// the params file that we are going to output
		$dsbuildParams = new BaseObject;

		// build up the list of settings to write out
		foreach($hosts as $hostName => $hostProps) {
			// what is the host's IP address?
			$ipAddress = $st->fromHost($hostName)->getIpAddress();

			$propName = $hostName . '_ipv4Address';
			$dsbuildParams->$propName = $ipAddress;
			if (isset($hostProps->params)) {
				$dsbuildParams->mergeFrom($hostProps->params);
			}
		}

		// add in the IP address of where Storyplayer is running
		$dsbuildParams->storyplayer_ipv4Address = $st->fromConfig()->get('storyplayer.ipAddress');

		// write them out
		$this->writeDsbuildParamsFile((array)$dsbuildParams);

		// at this point, we are ready to attempt provisioning
		//
		// provision each host in the order that they're listed
		foreach($hosts as $hostName => $hostProps) {
			// which dsbuildfile are we going to run?
			$dsbuildFilename = "";
			$candidateFilenames = [
				"dsbuildfile-" . $hostName,
				"dsbuildfile",
			];
			foreach ($candidateFilenames as $candidateFilename) {
				if (file_exists($candidateFilename)) {
					$dsbuildFilename = $candidateFilename;
					break;
				}
			}
			if ($dsbuildFilename == "") {
				// there is no dsbuildfile at all to run
				$log->endAction("cannot find dsbuildfile to run :(");
				throw new E5xx_ActionFailed(__METHOD__, "no dsbuildfile to run");
			}

			// at this point, we are ready to provision
			$commandRunner = new CommandRunner();
			$command = 'vagrant ssh -c "sudo bash /vagrant/' . $dsbuildFilename . '" "' . $hostName . '"';
			$result = $commandRunner->runSilently($st, $command);

			// what happened?
			if (!$result->didCommandSucceed()) {
				throw new E5xx_ActionFailed(__METHOD__, "provisioning failed");
			}
		}

		// all done
		$log->endAction();
	}

	/**
	 * @param string $inventoryFolder
	 */
	protected function writeDsbuildParamsFile($vars)
	{
		// shorthand
		$st = $this->st;

		// what are we doing?
		$log = $st->startAction("write dsbuild.yml");

		// what is the path to the file?
		$filename = "dsbuild.yml";

		// write the data
		$st->usingYamlFile($filename)->writeDataToFile($vars);

		// all done
		$log->endAction();
	}
}
