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

require_once 'PHP/Depend/Code/NodeVisitor.php';
require_once 'PHP/Depend/Metrics/PackageMetrics.php';

/**
 * This visitor generates the metrics for the analyzed packages.
 *
 * @category  QualityAssurance
 * @package   PHP_Depend
 * @author    Manuel Pichler <mapi@manuel-pichler.de>
 * @copyright 2008 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: 0.1.2
 * @link      http://www.manuel-pichler.de/
 */
class PHP_Depend_Metrics_PackageMetricsVisitor implements PHP_Depend_Code_NodeVisitor
{
    /**
     * The package data.
     *
     * @type array<array>
     * @var array(string=>array) $data
     */
    protected $data = array();
    
    /**
     * The generated project metrics.
     *
     * @type ArrayIterator
     * @var ArrayIterator $metrics
     */
    protected $metrics = null;
    
    /**
     * Returns the generated project metrics.
     *
     * @return array(string=>PHP_Depend_Metrics_PackageMetrics)
     */
    public function getPackageMetrics()
    {
        if ($this->metrics !== null) {
            return $this->metrics;
        }

        $metrics = array();
        foreach ($this->data as $pkg => $data) {
            $metrics[$pkg] = new PHP_Depend_Metrics_PackageMetrics(
                $data['pkg'], 
                $data['cc'],
                $data['ac'],
                $data['ca'],
                $data['ce']
            );
        }
        $this->metrics = new ArrayIterator($metrics);
        
        return $this->metrics;
    }
    
    /**
     * Visits a function node. 
     *
     * @param PHP_Depend_Code_Function $function The current function node.
     * 
     * @return void
     */
    public function visitFunction(PHP_Depend_Code_Function $function)
    {
        // TODO: Implement functions
    }
    
    /**
     * Visits a method node. 
     *
     * @param PHP_Depend_Code_Class $method The method class node.
     * 
     * @return void
     */
    public function visitMethod(PHP_Depend_Code_Method $method)
    {
        $pkgName = $method->getClass()->getPackage()->getName();
        
        foreach ($method->getDependencies() as $dep) {
            $depPkgName = $dep->getPackage()->getName();
            
            if ($dep->getPackage() !== $method->getClass()->getPackage()) {
                $this->initPackage($dep->getPackage());
            
                if (!in_array($dep->getPackage(), $this->data[$pkgName]['ce'], true)) {
                    $this->data[$pkgName]['ce'][] = $dep->getPackage();
                }
                if (!in_array($method->getClass()->getPackage(), $this->data[$depPkgName]['ca'], true)) {
                    $this->data[$depPkgName]['ca'][] = $method->getClass()->getPackage();
                }
            }
        }
    }
    
    /**
     * Visits a package node. 
     *
     * @param PHP_Depend_Code_Class $package The package class node.
     * 
     * @return void
     */
    public function visitPackage(PHP_Depend_Code_Package $package)
    {
        foreach ($package->getClasses() as $class) {
            $class->accept($this);
        }
        
        foreach ($package->getFunctions() as $function) {
            $function->accept($this);
        }
    }
    
    /**
     * Visits a class node. 
     *
     * @param PHP_Depend_Code_Class $class The current class node.
     * 
     * @return void
     */
    public function visitClass(PHP_Depend_Code_Class $class)
    {
        $pkgName = $class->getPackage()->getName();
        
        $this->initPackage($class->getPackage());
        
        if ($class->isAbstract()) {
            $this->data[$pkgName]['ac'][] = $class;
        } else {
            $this->data[$pkgName]['cc'][] = $class;
        }
        
        foreach ($class->getDependencies() as $dep) {
            $depPkgName = $dep->getPackage()->getName();
            
            if ($dep->getPackage() !== $class->getPackage()) {
           
                $this->initPackage($dep->getPackage());
                
                if (!in_array($dep->getPackage(), $this->data[$pkgName]['ce'], true)) {
                    $this->data[$pkgName]['ce'][] = $dep->getPackage();
                }
                if (!in_array($class->getPackage(), $this->data[$depPkgName]['ca'], true)) {
                    $this->data[$depPkgName]['ca'][] = $class->getPackage();
                }
            }
        }

        foreach ($class->getMethods() as $method) {
            $method->accept($this);
        }
    }
    
    /**
     * Initializes the a data record for the given package object.
     *
     * @param PHP_Depend_Code_Package $package The context package object.
     * 
     * @return void
     */
    protected function initPackage(PHP_Depend_Code_Package $package)
    {
        $name = $package->getName();
        
        if (!isset($this->data[$name])) {
            $this->data[$name] = array(
                'cc'   =>  array(),
                'ac'   =>  array(),
                'ca'   =>  array(),
                'ce'   =>  array(),
                'pkg'  =>  $package
            );
        }
    }
}