<?= "<?php\n" ?>

namespace equnit\<?= $class->getNamespaceName() ?>;

/*{{{ Loader */
require_once __DIR__.'/<?= $rel_root ?>equnit/Loader.php';
\equnit\Loader::registerBaseConstants();
\equnit\Loader::registerAutoload();
/* Loader }}}*/

use PHPUnit_Framework_TestCase;
use <?= $class->name ?>;

/**
 * @testof <?= $file."\n" ?>
 */
class <?= $class->getShortName() ?>Test extends PHPUnit_Framework_TestCase
{

    protected $_inst = null;

# @section PROVIDERS
<? foreach($methods as $method): ?>
    # @subsection <?= $method->name."\n" ?>
    /**
     * @see test<?= ucfirst($method->name)."\n" ?>
     */
    public function provider<?= ucfirst($method->name) ?>()
    {
        
    }<?= "\n" ?>
<?     foreach($method->getDocThrows() as $exception): ?>
    /**
     * @see test<?= ucfirst($method->name).'_'.(new \ReflectionClass($exception))->getShortName()."\n" ?>
     */
    public function provider<?= ucfirst($method->name).'_'.(new \ReflectionClass($exception))->getShortName() ?>()
    {
        
    }<?= "\n" ?>
<?     endforeach; ?>
    # @endsubsection <?= $method->name."\n\n" ?>
<? endforeach; ?>
# @endsection PROVIDERS

# @section TESTS
<? foreach($methods as $method): ?>
    # @subsection <?= $method->name."\n" ?>
    /**
     * @dataProvider provider<?= ucfirst($method->name)."\n" ?>
     */
    public function test<?= ucfirst($method->name) ?>()
    {
        
    }<?= "\n" ?>
<?     foreach($method->getDocThrows() as $exception): ?>
    /**
     * @dataProvider provider<?= ucfirst($method->name).'_'.(new \ReflectionClass($exception))->getShortName()."\n" ?>
     * @expectedException <?= $exception."\n" ?>
     */
    public function test<?= ucfirst($method->name).'_'.(new \ReflectionClass($exception))->getShortName() ?>()
    {
        
    }<?= "\n" ?>
<?     endforeach; ?>
    # @endsubsection <?= $method->name."\n" ?>
<? endforeach; ?>
# @endsection TESTS

    protected function inst()
    {
        if(!$this->_inst)
            $this->inst = $this->instNew();
        return $this->_inst;
    }

    protected function instNew()
    {
        return new <?= $class->getShortName() ?>();
    }

}
