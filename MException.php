<?php
// +----------------------------------------------------------------------
// | Buddy Framework 
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://buddy.woshimaijia.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: xinqiyang <xinqiyang@gmail.com>
// +----------------------------------------------------------------------

/**
 * Buddy Exception
 * @author xinqiyang
 *
 */
class MException extends Exception
{
    /**
     *
     * @var null|Exception
     */
    private $_previous = null;

    /**
     * counstruct
     *
     * @param string $message
     * @param int $code
     * @param Exception $previous
     * @return void
     */
    public function  __construct($message = '', $code=0, $previous = null) {
        if(version_compare(PHP_VERSION, '5.3.0','<'))
        {
            parent::__construct($message,(int)$code);
            $this->_previous = $previous;
        } else {
            parent::__construct($message,(int)$code,$previous);
        }
    }

    /**
     * reload
     * For PHP < 5.3.0,provides access to the get Previous() method.
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function  __call($method,array $args) {
        if('getprevious' == strtolower($method))
        {
            return $this->_getPrevious();
        }
        return null;
    }

    /**
     * show Exception error
     *
     * @return string
     */
    public function __toString()
    {
        if(version_compare(PHP_VERSION,'5.3.0','<'))
        {
            if(null !== ($e = $this->getPrevious())) {
                return $e->__toString()
                        . "\n\nNext "
                        . parent::__toString();
            }
        }
        return parent::__toString();
    }

    /**
     * return previous exception
     * @return Exception|null
     */
    protected function _getPrevious()
    {
        return $this->_previous;
    }


}