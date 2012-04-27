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

require_once 'PHP/Depend/Code/Class.php';
require_once 'PHP/Depend/Code/NodeBuilder.php'; 
require_once 'PHP/Depend/Code/NodeIterator.php';
require_once 'PHP/Depend/Code/Function.php';
require_once 'PHP/Depend/Code/Method.php';
require_once 'PHP/Depend/Code/Package.php';

/**
 * Default code tree builder implementation.
 *
 * @category  QualityAssurance
 * @package   PHP_Depend
 * @author    Manuel Pichler <mapi@manuel-pichler.de>
 * @copyright 2008 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: 0.1.2
 * @link      http://www.manuel-pichler.de/
 */
class PHP_Depend_Code_DefaultBuilder implements PHP_Depend_Code_NodeBuilder
{
    /**
     * Default package which contains all functions and classes with an unknown 
     * scope. 
     *
     * @type PHP_Depend_Code_Package
     * @var PHP_Depend_Code_Package $defaultPackage
     */
    protected $defaultPackage = null;
    
    /**
     * All generated {@link PHP_Depend_Code_Class} objects
     *
     * @type array<PHP_Depend_Code_Class>
     * @var array(string=>PHP_Depend_Code_Class) $classes
     */
    protected $classes = array();
    
    /**
     * All generated {@link PHP_Depend_Code_Package} objects
     *
     * @type array<PHP_Depend_Code_Package>
     * @var array(string=>PHP_Depend_Code_Package) $packages
     */
    protected $packages = array();
    
    /**
     * Constructs a new builder instance.
     */
    public function __construct()
    {
        $this->defaultPackage = new PHP_Depend_Code_Package(self::DEFAULT_PACKAGE);
    }
    
    /**
     * Builds a new package instance.
     *
     * @param string $name       The class name.
     * @param string $sourceFile The source file for the class.
     * 
     * @return PHP_Depend_Code_Class The created class object.
     */
    public function buildClass($name, $sourceFile = null)
    {
        if (!isset($this->classes[$name])) {
            $this->classes[$name] = new PHP_Depend_Code_Class($name, $sourceFile);
            
            $this->defaultPackage->addClass($this->classes[$name]);
        }
        if ($sourceFile !== null) {
            $this->classes[$name]->setSourceFile($sourceFile);
        }
        return $this->classes[$name];
    }
    
    /**
     * Builds a new method instance.
     *
     * @param string $name The method name.
     * 
     * @return PHP_Depend_Code_Method The created class method object.
     */
    public function buildMethod($name)
    {
        return new PHP_Depend_Code_Method($name);
    }
    
    /**
     * Builds a new package instance.
     *
     * @param string $name The package name.
     * 
     * @return PHP_Depend_Code_Package The created package object.
     */
    public function buildPackage($name)
    {
        if (!isset($this->packages[$name])) {
            $this->packages[$name] = new PHP_Depend_Code_Package($name);
        }
        return $this->packages[$name];
    }
    
    /**
     * Builds a new function instance.
     *
     * @param string $name The function name.
     * 
     * @return PHP_Depend_Code_Function The function instance.
     */
    public function buildFunction($name)
    {
        // Create new function
        $function = new PHP_Depend_Code_Function($name);
        // Add to default package
        $this->defaultPackage->addFunction($function);
        
        return $function;
    }
    
    /**
     * Returns an iterator with all generated {@link PHP_Depend_Code_Package}
     * objects.
     *
     * @return PHP_Depend_Code_NodeIterator
     */
    public function getIterator()
    {
        return $this->getPackages();
    }
    
    /**
     * Returns an iterator with all generated {@link PHP_Depend_Code_Package}
     * objects.
     *
     * @return PHP_Depend_Code_NodeIterator
     */
    public function getPackages()
    {
        return new PHP_Depend_Code_NodeIterator($this->packages);
    }
}