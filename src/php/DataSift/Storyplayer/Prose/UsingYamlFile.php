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

use DataSift\Storyplayer\PlayerLib\StoryTeller;
use DataSift\Stone\DataLib\DataPrinter;
use Symfony\Component\Yaml\Dumper;

/**
 * Support for working with YAML files
 *
 * @category  Libraries
 * @package   Storyplayer/Prose
 * @author    Stuart Herbert <stuart.herbert@datasift.com>
 * @copyright 2011-present Mediasift Ltd www.datasift.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://datasift.github.io/storyplayer
 */
class UsingYamlFile extends Prose
{
	public function __construct(StoryTeller $st, $args)
	{
		// call our parent constructor
		parent::__construct($st, $args);

		// $args[0] will be our filename
		if (!isset($args[0])) {
			throw new E5xx_ActionFailed(__METHOD__, "Param #0 needs to be the name of the file to work with");
		}
	}

	public function writeDataToFile($params)
	{
		// shorthand
		$st = $this->st;
		$filename = $this->args[0];

		// what are we doing?
		$printer = new DataPrinter();
		$logParams = $printer->convertToString($params);
		$log = $st->startAction("create YAML file '{$filename}' with contents '{$logParams}'");

		// create an instance of the Symfony YAML writer
		$writer = new Dumper();

		// create the YAML data
		$yamlData = $writer->dump($params, 2);
		if (!is_string($yamlData) || strlen($yamlData) < 6) {
			throw new E5xx_ActionFailed(__METHOD__, "unable to convert data to YAML");
		}

		// prepend the YAML marker
		$yamlData = '---' . PHP_EOL . $yamlData;

		// write the file
		//
		// the loose FALSE test here is exactly what we want, because we want to catch
		// both the situation when the write fails, and when there's zero bytes written
		if (!file_put_contents($filename, $yamlData)) {
			throw new E5xx_ActionFailed(__METHOD__, "unable to write file '{$filename}'");
		}

		// all done
		$log->endAction();
	}
}