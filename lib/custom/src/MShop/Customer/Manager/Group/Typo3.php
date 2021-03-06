<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 * @package MShop
 * @subpackage Customer
 */


/**
 * TYPO3 implementation of the customer group class
 *
 * @package MShop
 * @subpackage Customer
 */
class MShop_Customer_Manager_Group_Typo3
	extends MShop_Customer_Manager_Group_Default
	implements MShop_Customer_Manager_Group_Interface
{
	private $_searchConfig = array(
		'customer.group.id' => array(
			'code' => 'customer.group.id',
			'internalcode' => 't3feg."uid"',
			'label' => 'Customer group ID',
			'type' => 'integer',
			'internaltype' => MW_DB_Statement_Abstract::PARAM_INT,
		),
		'customer.group.code' => array(
			'code' => 'customer.group.code',
			'internalcode' => 't3feg."uid"',
			'label' => 'Customer group code',
			'type' => 'string',
			'internaltype' => MW_DB_Statement_Abstract::PARAM_STR,
		),
		'customer.group.label' => array(
			'code' => 'customer.group.label',
			'internalcode' => 't3feg."title"',
			'label' => 'Customer group label',
			'type' => 'string',
			'internaltype' => MW_DB_Statement_Abstract::PARAM_STR,
		),
		'customer.group.ctime'=> array(
			'code' => 'customer.group.ctime',
			'internalcode' => 't3feg."crdate"',
			'label' => 'Customer group creation time',
			'type' => 'datetime',
			'internaltype' => MW_DB_Statement_Abstract::PARAM_STR,
		),
		'customer.group.mtime'=> array(
			'code' => 'customer.group.mtime',
			'internalcode' => 't3feg."tstamp"',
			'label' => 'Customer group modification time',
			'type' => 'datetime',
			'internaltype' => MW_DB_Statement_Abstract::PARAM_STR,
		),
		'customer.group.editor'=> array(
			'code' => 'customer.group.editor',
			'internalcode' => '1',
			'label' => 'Customer group editor',
			'type' => 'string',
			'internaltype' => MW_DB_Statement_Abstract::PARAM_STR,
		),
	);

	private $_plugins = array();
	private $_reverse = array();


	/**
	 * Initializes the customer group manager object
	 *
	 * @param MShop_Context_Interface $context Context object with required objects
	 */
	public function __construct( MShop_Context_Item_Interface $context )
	{
		parent::__construct( $context );

		$plugin = new MW_Common_Criteria_Plugin_T3Datetime();
		$this->_plugins['customer.ctime'] = $this->_reverse['crdate'] = $plugin;
		$this->_plugins['customer.mtime'] = $this->_reverse['tstamp'] = $plugin;
	}


	/**
	 * Removes old entries from the database
	 *
	 * @param integer[] $siteids List of IDs for sites whose entries should be deleted
	 */
	public function cleanup( array $siteids )
	{
		$path = 'classes/customer/manager/group/submanagers';

		foreach( $this->_getContext()->getConfig()->get( $path, array() ) as $domain ) {
			$this->getSubManager( $domain )->cleanup( $siteids );
		}
	}


	/**
	 * Removes multiple items specified by their IDs
	 *
	 * @param array $ids List of IDs
	 */
	public function deleteItems( array $ids )
	{
		throw new MShop_Customer_Exception( sprintf( 'Deleting groups is not supported, please use the TYPO3 backend' ) );
	}


	/**
	 * Returns the attributes that can be used for searching
	 *
	 * @param boolean $withsub Return attributes of sub-managers too if true
	 * @return array List of attribute items implementing MW_Common_Criteria_Attribute_Interface
	 */
	public function getSearchAttributes( $withsub = true )
	{
		$path = 'classes/customer/manager/group/submanagers';

		return $this->_getSearchAttributes( $this->_searchConfig, $path, array(), $withsub );
	}


	/**
	 * Returns a new manager for customer group extensions
	 *
	 * @param string $manager Name of the sub manager type in lower case
	 * @param string|null $name Name of the implementation, will be from configuration (or Default) if null
	 * @return MShop_Common_Manager_Interface Manager for different extensions
	 */
	public function getSubManager( $manager, $name = null )
	{
		return $this->_getSubManager( 'customer/group', $manager, ( $name === null ? 'Typo3' : $name ) );
	}


	/**
	 * Inserts a new or updates an existing customer group item
	 *
	 * @param MShop_Customer_Item_Group_Interface $item Customer group item
	 * @param boolean $fetch True if the new ID should be returned in the item
	 */
	public function saveItem( MShop_Common_Item_Interface $item, $fetch = true )
	{
		throw new MShop_Customer_Exception( sprintf( 'Saving groups is not supported, please use the TYPO3 backend' ) );
	}


	/**
	 * Returns the item objects matched by the given search criteria.
	 *
	 * @param MW_Common_Criteria_Interface $search Search criteria object
	 * @param array $ref List of domain items that should be fetched too
	 * @param integer &$total Number of items that are available in total
	 * @return array List of items implementing MShop_Customer_Item_Group_Interface
	 * @throws MShop_Exception If retrieving items failed
	 */
	public function searchItems( MW_Common_Criteria_Interface $search, array $ref = array(), &$total = null )
	{
		$map = array();
		$context = $this->_getContext();

		$dbm = $context->getDatabaseManager();
		$dbname = $this->_getResourceName();
		$conn = $dbm->acquire( $dbname );

		try
		{
			$required = array( 'customer.group' );
			$level = MShop_Locale_Manager_Abstract::SITE_ALL;

			/** mshop/customer/manager/group/typo3/item/search
			 * Retrieves the records matched by the given criteria in the database
			 *
			 * Fetches the records matched by the given criteria from the customer
			 * database. The records must be from one of the sites that are
			 * configured via the context item. If the current site is part of
			 * a tree of sites, the SELECT statement can retrieve all records
			 * from the current site and the complete sub-tree of sites.
			 *
			 * As the records can normally be limited by criteria from sub-managers,
			 * their tables must be joined in the SQL context. This is done by
			 * using the "internaldeps" property from the definition of the ID
			 * column of the sub-managers. These internal dependencies specify
			 * the JOIN between the tables and the used columns for joining. The
			 * ":joins" placeholder is then replaced by the JOIN strings from
			 * the sub-managers.
			 *
			 * To limit the records matched, conditions can be added to the given
			 * criteria object. It can contain comparisons like column names that
			 * must match specific values which can be combined by AND, OR or NOT
			 * operators. The resulting string of SQL conditions replaces the
			 * ":cond" placeholder before the statement is sent to the database
			 * server.
			 *
			 * If the records that are retrieved should be ordered by one or more
			 * columns, the generated string of column / sort direction pairs
			 * replaces the ":order" placeholder. In case no ordering is required,
			 * the complete ORDER BY part including the "\/*-orderby*\/...\/*orderby-*\/"
			 * markers is removed to speed up retrieving the records. Columns of
			 * sub-managers can also be used for ordering the result set but then
			 * no index can be used.
			 *
			 * The number of returned records can be limited and can start at any
			 * number between the begining and the end of the result set. For that
			 * the ":size" and ":start" placeholders are replaced by the
			 * corresponding values from the criteria object. The default values
			 * are 0 for the start and 100 for the size value.
			 *
			 * The SQL statement should conform to the ANSI standard to be
			 * compatible with most relational database systems. This also
			 * includes using double quotes for table and column names.
			 *
			 * @param string SQL statement for searching items
			 * @since 2015.08
			 * @category Developer
			 * @see mshop/customer/manager/group/typo3/item/count
			 */
			$cfgPathSearch = 'mshop/customer/manager/group/typo3/item/search';

			/** mshop/customer/manager/group/typo3/item/count
			 * Counts the number of records matched by the given criteria in the database
			 *
			 * Counts all records matched by the given criteria from the customer
			 * database. The records must be from one of the sites that are
			 * configured via the context item. If the current site is part of
			 * a tree of sites, the statement can count all records from the
			 * current site and the complete sub-tree of sites.
			 *
			 * As the records can normally be limited by criteria from sub-managers,
			 * their tables must be joined in the SQL context. This is done by
			 * using the "internaldeps" property from the definition of the ID
			 * column of the sub-managers. These internal dependencies specify
			 * the JOIN between the tables and the used columns for joining. The
			 * ":joins" placeholder is then replaced by the JOIN strings from
			 * the sub-managers.
			 *
			 * To limit the records matched, conditions can be added to the given
			 * criteria object. It can contain comparisons like column names that
			 * must match specific values which can be combined by AND, OR or NOT
			 * operators. The resulting string of SQL conditions replaces the
			 * ":cond" placeholder before the statement is sent to the database
			 * server.
			 *
			 * Both, the strings for ":joins" and for ":cond" are the same as for
			 * the "search" SQL statement.
			 *
			 * Contrary to the "search" statement, it doesn't return any records
			 * but instead the number of records that have been found. As counting
			 * thousands of records can be a long running task, the maximum number
			 * of counted records is limited for performance reasons.
			 *
			 * The SQL statement should conform to the ANSI standard to be
			 * compatible with most relational database systems. This also
			 * includes using double quotes for table and column names.
			 *
			 * @param string SQL statement for counting items
			 * @since 2015.08
			 * @category Developer
			 * @see mshop/customer/manager/group/typo3/item/search
			 */
			$cfgPathCount = 'mshop/customer/manager/group/typo3/item/count';

			$results = $this->_searchItems( $conn, $search, $cfgPathSearch, $cfgPathCount, $required, $total, $level );

			while( ( $row = $results->fetch() ) !== false ) {
				$map[$row['id']] = $this->_createItem( $row );
			}

			$dbm->release( $conn, $dbname );
		}
		catch( Exception $e )
		{
			$dbm->release( $conn, $dbname );
			throw $e;
		}

		return $map;
	}


	/**
	 * Creates a new customer item.
	 *
	 * @param array $values List of attributes for customer item
	 * @param array $listItems List items associated to the customer item
	 * @param array $refItems Items referenced by the customer item via the list items
	 * @return MShop_Customer_Item_Interface New customer item
	 */
	protected function _createItem( array $values = array(), array $listItems = array(), array $refItems = array() )
	{
		$values['siteid'] = $this->_getContext()->getLocale()->getSiteId();

		if( array_key_exists( 'tstamp', $values ) ) {
			$values['mtime'] = $this->_reverse['tstamp']->reverse( $values['tstamp'] );
		}

		if( array_key_exists( 'crdate', $values ) ) {
			$values['ctime'] = $this->_reverse['crdate']->reverse( $values['crdate'] );
		}

		return new MShop_Customer_Item_Group_Default( $values );
	}
}
