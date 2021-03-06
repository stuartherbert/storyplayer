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
 * @package   Storyplayer/Injectables
 * @author    Stuart Herbert <stuart.herbert@datasift.com>
 * @copyright 2011-present Mediasift Ltd www.datasift.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://datasift.github.io/storyplayer
 */

namespace DataSift\Storyplayer\Injectables;

use DataSift\Stone\ObjectLib\BaseObject;

/**
 * support for the test environment that the user chooses
 *
 * @category  Libraries
 * @package   Storyplayer/Cli
 * @author    Stuart Herbert <stuart.herbert@datasift.com>
 * @copyright 2011-present Mediasift Ltd www.datasift.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://datasift.github.io/storyplayer
 */
trait ActiveTestEnvironmentConfigSupport
{
	public $activeTestEnvironmentName;
    public $activeTestEnvironmentConfig;

	public function initActiveTestEnvironmentConfigSupport($envName, $injectables)
	{
        // does the test environment exist?
        if (!isset($injectables->knownTestEnvironments->$envName)) {
            throw new E4xx_NoSuchTestEnvironment($envName);
        }

        // a helper to load the config
        $staticConfigManager = $injectables->staticConfigManager;

        // we need to store the test environment's config as a string,
        // as it will need expanding as we provision the test environment
        if (is_string($injectables->knownTestEnvironments->$envName)) {
            $this->activeTestEnvironmentConfig = json_encode($staticConfigManager->loadConfigFile($injectables->knownTestEnvironments->$envName));
        }
        else {
            $this->activeTestEnvironmentConfig = json_encode($injectables->knownTestEnvironments->$envName);
        }

        $this->validateActiveTestEnvironmentConfig(json_decode($this->activeTestEnvironmentConfig), $injectables);

        // remember the environment name
        $this->activeTestEnvironmentName = $envName;
	}

    protected function validateActiveTestEnvironmentConfig($testEnv, $injectables)
    {
        // shorthand
        $output = $injectables->output;

        foreach ($testEnv as $index => $group) {
            // do we have any machines defined?
            if (!isset($group->details, $group->details->machines)) {
                $output->logCliError("group #{$index} in your test environment config needs to define at least one machine");
                exit(1);
            }

            foreach ($group->details->machines as $name => $machine) {
                // does each machine have a role?
                if (!isset($machine->roles)) {
                    $output->logCliError("machine '{$name}' in your test environment defines no roles :(");
                    exit(1);
                }
                // is the roles an array?
                if (!is_array($machine->roles)) {
                    $output->logCliError("machine '{$name}' must provide an array of roles");
                    exit(1);
                }
            }
        }
    }
}
