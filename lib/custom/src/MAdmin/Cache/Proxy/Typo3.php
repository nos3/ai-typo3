<?php

/**
 * @license LGPLv3, http://www.gnu.org/copyleft/lgpl.html
 * @copyright Aimeos (aimeos.org), 2015
 * @package MAdmin
 * @subpackage Cache
 */


/**
 * Cache proxy for creating the TYPO3 cache object on demand.
 *
 * @package MAdmin
 * @subpackage Cache
 */
class MAdmin_Cache_Proxy_Typo3
	extends \MAdmin_Cache_Proxy_Default
	implements \MW_Cache_Interface
{
	private $_object;
	private $_context;
	private $_cache;


	/**
	 * Initializes the cache controller.
	 *
	 * @param \MShop_Context_Item_Interface $context MShop context object
	 * @param \TYPO3\Flow\Cache\Frontend\StringFrontend $cache Flow cache object
	 */
	public function __construct( \MShop_Context_Item_Interface $context, \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache )
	{
		$this->_context = $context;
		$this->_cache = $cache;
	}


	/**
	 * Returns the cache object or creates a new one if it doesn't exist yet.
	 *
	 * @return \MW_Cache_Interface Cache object
	 */
	protected function _getObject()
	{
		if( !isset( $this->_object ) )
		{
			$siteid = $this->_context->getLocale()->getSiteId();
			$conf = array( 'siteid' => $this->_context->getConfig()->get( 'madmin/cache/prefix' ) . $siteid );
			$this->_object = \MW_Cache_Factory::createManager( 'Typo3', $conf, $this->_cache );
		}

		return $this->_object;
	}
}
