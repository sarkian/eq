<?php
/**
 * Last Change: 2013 Oct 21, 19:07
 */

class SectionsTest
{

# @section PROVIDERS
    # @subsection myMethod
    /**
     * @see testMyMethod
     */
    public function providerMyMethod()
    {

    }
    
    /**
     * @see testMyMethod_Exception
     */
    public function providerMyMethod_Exception()
    {

    }

    /**
     * @see testMyMethod_OtherException
     */
    public function providerMyMethod_OtherException()
    {

    }
    # @endsubsection myMethod
    # @subsection otherMethod
    /**
     * @see testOtherMethod
     */
    public function providerOtherMethod()
    {

    }

    /**
     * @see testOtherMethod_Exception
     */
    public function providerOtherMethod_Exception()
    {
        
    }

    /**
     * @see testOtherMethod_OtherException
     */
    public function providerOtherMethod_OtherException()
    {

    }
    # @endsubsection otherMethod
# @endsection PROVIDERS
# @section TESTS
    # @subsection myMethod
    /**
     * @dataProvider providerMyMethod
     */
    public function testMyMethod()
    {

    }

    /**
     * @dataProvider providerMyMethod_Exception
     * @expectedException Exception
     */
    public function testMyMethod_Exception()
    {
        
    }

    /**
     * @dataProvider providerMyMethod_OtherException
     * @expectedException OtherException
     */
    public function testMyMethod_OtherException()
    {

    }
    # @endsubsection myMethod
    # @subsection otherMethod
    /**
     * @dataProvider providerOtherMethod
     */
    public function testOtherMethod()
    {

    }

    /**
     * @dataProvider providerOtherMethod_Exception
     * @expectedException Exception
     */
    public function testOtherMethod_Exception()
    {

    }

    /**
     * @dataProvider providerOtherMethod_OtherException
     * @expectedException OtherException
     */
    public function testOtherMethod_OtherException()
    {

    }
    # @endsubsection otherMethod
# @endsection TESTS

# @subsection a

# @endsubsection a


# @subsection a

# @endsubsection a


# @subsection a

# @endsubsection a


# @subsection a

# @endsubsection a


# @subsection a

# @endsubsection a


# @subsection a

# @endsubsection a


# @subsection a

# @endsubsection a


# @subsection a

# @endsubsection a

# @subsection a

# @endsubsection a


# @subsection a

# @endsubsection a



# @subsection a

# @endsubsection a


# @subsection a

# @endsubsection a




}
