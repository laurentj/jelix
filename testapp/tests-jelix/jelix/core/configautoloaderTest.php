<?php

class fakeConfigAutoloader extends jConfigAutoloader {

    function test_get_path($className) {
        return $this->getPath($className);
    }

}

class configautoloaderTest extends PHPUnit_Framework_TestCase {

    function testPathWithoutNamespaces() {

        $autoloader = new fakeConfigAutoloader((object) parse_ini_string('
[_autoload_class]
[_autoload_namespace]
[_autoload_namespacepathmap]
[_autoload_classpattern]
[_autoload_includepathmap]
[_autoload_includepath]
path[]="'.__DIR__.'/autoload/some|.php"
', true));
        $this->assertEquals(__DIR__.'/autoload/some/bateau.php', $autoloader->test_get_path('bateau'));
        $this->assertFalse($autoloader->test_get_path('bateauinconnu'));
        $this->assertEquals(__DIR__.'/autoload/some/bateau/sous.php', $autoloader->test_get_path('bateau_sous'));
        $this->assertEquals(__DIR__.'/autoload/some/bateau.php', $autoloader->test_get_path('\bateau'));
        $this->assertEquals(__DIR__.'/autoload/some/foo/bateau.php', $autoloader->test_get_path('\foo\bateau'));
    }
    
    function testClassPath() {
        $autoloader = new fakeConfigAutoloader((object) parse_ini_string('
[_autoload_namespace]
[_autoload_namespacepathmap]
[_autoload_classpattern]
[_autoload_includepathmap]
[_autoload_includepath]
[_autoload_class]
bateau="'.__DIR__.'/autoload/some/bateau.php"
foo\bateau="'.__DIR__.'/autoload/foobat.php"
', true));
        $this->assertEquals(__DIR__.'/autoload/some/bateau.php',$autoloader->test_get_path('bateau'));
        $this->assertFalse($autoloader->test_get_path('bateauinconnu'));
        $this->assertFalse($autoloader->test_get_path('bateau_sous'));
        $this->assertEquals(__DIR__.'/autoload/foobat.php',$autoloader->test_get_path('foo\bateau'));
        $this->assertFalse($autoloader->test_get_path('unknown'));
    }

    function testPathWithNamespacePSR0() {
        $autoloader = new fakeConfigAutoloader((object) parse_ini_string('
[_autoload_class]
[_autoload_namespacepathmap]
[_autoload_classpattern]
[_autoload_includepathmap]
[_autoload_includepath]
[_autoload_namespace]
foo = "'.__DIR__.'/autoload/ns/bar|.php"
blo_u\bl_i="'.__DIR__.'/autoload/ns/other|.php"
', true));
        $this->assertEquals(__DIR__.'/autoload/ns/bar/foo.php', $autoloader->test_get_path('foo'));
        $this->assertEquals(__DIR__.'/autoload/ns/bar/foo.php', $autoloader->test_get_path('\foo'));
        $this->assertEquals(__DIR__.'/autoload/ns/bar/foo/bar/myclass.php', $autoloader->test_get_path('foo\bar\myclass'));
        $this->assertEquals(__DIR__.'/autoload/ns/bar/foo/bar/my/class.php', $autoloader->test_get_path('\foo\bar\my_class'));
        $this->assertEquals(__DIR__.'/autoload/ns/other/blo_u/bl_i/bla/p.php', $autoloader->test_get_path('blo_u\bl_i\bla_p'));
        $this->assertEquals(__DIR__.'/autoload/ns/other/blo_u/bl/i.php', $autoloader->test_get_path('blo_u\bl_i'));
    }

    function testPathWithNamespaceNotPSR0() {
        $autoloader = new fakeConfigAutoloader((object) parse_ini_string('
[_autoload_class]
[_autoload_namespace]
[_autoload_classpattern]
[_autoload_includepathmap]
[_autoload_includepath]
[_autoload_namespacepathmap]
foo = "'.__DIR__.'/autoload/ns/bar/foo|.php"
', true));
        $this->assertEquals(__DIR__.'/autoload/ns/bar/foo/bar/myclass.php', $autoloader->test_get_path('\foo\bar\myclass'));
        $this->assertEquals(__DIR__.'/autoload/ns/bar/foo/bar/my/class.php', $autoloader->test_get_path('\foo\bar\my_class'));
        $this->assertEquals(__DIR__.'/autoload/ns/bar/foo/myclass2.php', $autoloader->test_get_path('\foo\myclass2'));
    }

    function testClassRegPath() {
        $autoloader = new fakeConfigAutoloader((object) parse_ini_string('
[_autoload_class]
[_autoload_namespace]
[_autoload_namespacepathmap]
[_autoload_includepathmap]
[_autoload_includepath]
[_autoload_classpattern]
regexp[]="/^bat/"
path[]="'.__DIR__.'/autoload/some|.php"
', true));

        $this->assertEquals(__DIR__.'/autoload/some/bateau.php',  $autoloader->test_get_path('bateau'));
        $this->assertEquals(__DIR__.'/autoload/some/batman.php',  $autoloader->test_get_path('batman'));
        $this->assertFalse($autoloader->test_get_path('unknown'));
    }

}
