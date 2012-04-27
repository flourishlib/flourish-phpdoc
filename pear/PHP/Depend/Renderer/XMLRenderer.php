<?php
/**
 * This file is part of PHP_Depend.
 * 
 * PHP Version 5
 *
 * Copyright (c) 2008, Manuel Pichler <mapi@pmanuel-pichler.de>.
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
 *   * Neither the name of Manuel Pichler nor the names of his
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
 * @category  QualityAssurance
 * @package   PHP_Depend
 * @author    Manuel Pichler <mapi@manuel-pichler.de>
 * @copyright 2008 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   SVN: $Id$
 * @link      http://www.manuel-pichler.de/
 */

require_once 'PHP/Depend/Renderer.php';

/**
 * Generates an xml document with the aggregated metrics. The format is borrowed
 * from <a href="http://clarkware.com/software/JDepend.html">JDepend</a>.
 *
 * @category  QualityAssurance
 * @package   PHP_Depend
 * @author    Manuel Pichler <mapi@manuel-pichler.de>
 * @copyright 2008 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: 0.1.2
 * @link      http://www.manuel-pichler.de/
 */

class PHP_Depend_Renderer_XMLRenderer implements PHP_Depend_Renderer
{
	/**
	 * The output file.
	 *
	 * @type string
	 * @var string $fileName
	 */
	protected $fileName = null;
	
	/**
	 * Constructs a new xml renderer. 
	 * 
	 * The optional <b>$fileName</b> parameter points to the xml out file. If this
	 * parameter is not given the renderer outputs to stdout
	 *
	 * @param string $fileName The output file.
	 */
	public function __construct($fileName = null)
	{
		$this->fileName = $fileName;
	}

	/**
	 * Generates the package xml metrics.
	 *
	 * @param Iterator $metrics The aggregated metrics.
	 * 
	 * @return void
	 */
	public function render(Iterator $metrics)
	{
		$dom = new DOMDocument('1.0', 'UTF-8');
		
		$dom->formatOutput = true;
		
		//$root = $dom->appendChild($dom->createElement('PHPDepend'));
		//$pkgs = $root->appendChild($dom->createElement('Packages'));
		
		// First sort the metrics
		$sortedMetrics = array();
		foreach ($metrics as $metric) {
			if ($metric->getName() !== PHP_Depend_Code_NodeBuilder::DEFAULT_PACKAGE) {
				$sortedMetrics[$metric->getName()] = $metric;
			}
		}
		ksort($sortedMetrics);
		
		$dependencies = array();
		
		foreach ($sortedMetrics as $metric) {
			$dependencies[$metric->getName()] = array();
			
			$deps = array();
			foreach ($metric->getEfferents() as $dep) {
				if ($dep->getName() == 'global' || $dep->getName() == $metric->getName()) {
					continue;	
				}
				$deps[] = $dep->getName();
			}
			
			natcasesort($deps);
			
			
			
			foreach ($deps as $dep) {
				$dependencies[$metric->getName()][] = $dep;
			}
		   
		}
		
		// Expand the dependencies for all files
		//$expanded_dependencies = array();
		//foreach ($dependencies as $class => $depend_classes) {
		//	$found_deps = array();
		//	$parsed_deps = array();
		//	find_dependencies($class, $found_deps, $parsed_deps, $dependencies);
		//	$expanded_dependencies[$class] = array_merge(array_diff($found_deps, array($class))); 	
		//	sort($expanded_dependencies[$class]);	
		//}
		
		$used_bys = array();
		foreach ($dependencies as $class => $deps) {
			foreach ($deps as $dependency) {
				if (!isset($used_bys[$dependency])) {
					$used_bys[$dependency] = array(); 		
				}
				$used_bys[$dependency][] = $class;
				sort($used_bys[$dependency]);
			}	
		}
		ksort($used_bys);

		//if (count($cycles) > 0) {
		//	$cyclesElem = $root->appendChild($dom->createElement('Cycles'));
		//	foreach ($cycles as $name => $cycle) {
		//		$pkgElem = $cyclesElem->appendChild($dom->createElement('Package'));
		//		$pkgElem->setAttribute('Name', $name);
		//		foreach ($cycle as $pkg) {
		//			$pkgElem->appendChild($dom->createElement('Package'))
		//					->appendChild($dom->createTextNode($pkg->getName()));
		//		}
		//	}
		//}

		if ($this->fileName === null) {
			//print_r($expanded_dependencies);
		} else {
			$metrics_dir   = '/var/tmp/flourish-phpdoc/output/depend/';
			file_put_contents($metrics_dir . 'uses.inc', serialize($dependencies));
			file_put_contents($metrics_dir . 'usedby.inc', serialize($used_bys));
		}
	}
}

function find_dependencies($class, &$found_deps, &$parsed_deps, &$all_deps)
{
	$parsed_deps[$class] = TRUE;
	if (!in_array($class, $found_deps)) {
		$found_deps[] = $class;
	}
	
	foreach ($all_deps[$class] as $dep) {
		if (isset($parsed_deps[$dep])) {
			continue;	
		}
		
		find_dependencies($dep, $found_deps, $parsed_deps, $all_deps);
	}
}