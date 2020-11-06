<?php

namespace MongoCute\MongoCute;

use Composer\Autoload\ClassLoader;
use Dotenv\Dotenv;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\DeleteResult;
use MongoDB\InsertOneResult;
use MongoDB\UpdateResult;

/**
 * Class QueryBuilder
 *
 * @method QueryBuilder db( $name ) Select database
 * @method QueryBuilder table( $name ) Select Collection/Table
 * @method QueryBuilder where( string $name, $value = '', string $operator = '$eq' ) Where equals to value
 * @method QueryBuilder whereEqual( string $name, $value ) Where equals to value
 * @method QueryBuilder whereNot( string $name, $value ) Where not equals to value
 * @method QueryBuilder whereIn( string $name, array $values ) Where equals to value
 * @method QueryBuilder whereNotIn( string $name, array $values ) Where equals to value
 * @method QueryBuilder whereGreaterThan( string $name, $value ) Where equals to value
 * @method QueryBuilder whereGreaterThanOrEqual( string $name, $value ) Where equals to value
 * @method QueryBuilder whereLessThan( string $name, $value ) Where equals to value
 * @method QueryBuilder whereLessThanOrEqual( string $name, $value ) Where equals to value
 * @method QueryBuilder orWhere( string $name, $value = '', string $operator = '$eq' ) Where equals to value
 * @method QueryBuilder orWhereEqual( string $name, $value ) Where equals to value
 * @method QueryBuilder orWhereNot( string $name, $value ) Where not equals to value
 * @method QueryBuilder orWhereIn( string $name, array $values ) Where equals to value
 * @method QueryBuilder orWhereNotIn( string $name, array $values ) Where equals to value
 * @method QueryBuilder orWhereGreaterThan( string $name, $value ) Where equals to value
 * @method QueryBuilder orWhereGreaterThanOrEqual( string $name, $value ) Where equals to value
 * @method QueryBuilder orWhereLessThan( string $name, $value ) Where equals to value
 * @method QueryBuilder orWhereLessThanOrEqual( string $name, $value ) Where equals to value
 * @method QueryBuilder select( array $fields ) select fields from collection
 * @method QueryBuilder orderby( array $fields, $order = 'ASC' ) order fields from collection
 * @method array get( int $count = 0 ) get result
 * @method array|object|null first() get first result only
 * @method \MongoDB\InsertOneResult create( array $data ) Insert an Doc into table
 * @method \MongoDB\InsertManyResult createMany( array $data ) Insert Docs into table
 * @method \MongoDB\UpdateResult update( array $data ) Update Docs
 * @method \MongoDB\DeleteResult delete() Delete Docs
 * @package MongoCute\MongoCute
 */
class QueryBuilder
{
	/**
	 * Databse credensials
	 */
	protected string $db_address;
	protected string $db_port;
	protected string $db_name;
	protected string $db_user;
	protected string $db_pass;

	/** @var bool $connected is database connected successfully */
	protected bool $connected = false;

	/**
	 * @var array $query_where
	 */
	protected array $query_where = [];

	/**
	 * @var array $query_orderby
	 */
	protected array $query_orderby = [];

	/**
	 * @var string $query_groupby
	 */
	protected $query_groupby;

	/**
	 * @var array $query_select
	 */
	protected $query_select = [];

	/**
	 * @var string $query_table
	 */
	protected string $query_table = '';

	/**
	 * @var bool $is_group_where
	 */
	protected bool $is_group_where = false;

	/**
	 * @var bool $is_count
	 */
	protected $is_count = false;

	protected static $current_depth_info = [
		'type' => '$and',
		'conditions' => [],
	];

	/**
	 * @var Client|Collection $mongo
	 */
	protected $mongo;

	/**
	 * this is allowed operators for where mysql
	 */
	protected const ALLOWED_OPERATORS = [ '$eq', '$ne', '$gt', '$gte', '$lt', '$lte', '$in', '$nin' ];

	/**
	 * Model constructor.
	 */
	public function __construct()
	{
		$ref       = new \ReflectionClass( ClassLoader::class );
		$envreader = Dotenv::createImmutable( dirname( $ref->getFileName() ) . '/../../' );
		$envreader = $envreader->safeLoad();

		$this->db_address = self::getEnv( 'MGCUTE_DB_ADDRESS', '127.0.0.1' );
		$this->db_port    = self::getEnv( 'MGCUTE_DB_PORT', '27017' );
		$this->db_name    = self::getEnv( 'MGCUTE_DB_NAME', '' );
		$this->db_user    = self::getEnv( 'MGCUTE_DB_USERNAME', '' );
		$this->db_pass    = self::getEnv( 'MGCUTE_DB_PASSWORD', '' );

		$userpass = $this->db_user ? "{$this->db_user}:{$this->db_pass}@" : '';
		$this->mongo = new Client( "mongodb://{$userpass}{$this->db_address}:{$this->db_port}" );

		try {
			$this->mongo->listDatabases();
			$this->connected = true;
		} catch ( MongoDB\Driver\Exception\ConnectionTimeoutException $e ) {
			$this->connected = false;
		}
	}

	public function __call( $name, $arguments )
	{
		return $this->_call( $name, $arguments );
	}

	public static function __callStatic( $name, $arguments )
	{
		$self = new static();
		return $self->_call( $name, $arguments );
	}

	/**
	 * handle builtin methods as static or non static
	 *
	 * @param $name
	 * @param $arguments
	 *
	 * @return $this|mixed|Model
	 */
	protected function _call( $name, $args )
	{
		$args[ 'arg1' ] = $args[ 'tb' ] = $args[ 'count' ] = $args[ 0 ] ?? '';
		$args[ 'arg2' ] = $args[ 'field1' ] = $args[ 1 ] ?? '';
		$args[ 'arg3' ] = $args[ 'join_operator' ] = $args[ 2 ] ?? '';
		$args[ 'arg4' ] = $args[ 3 ] ?? '';

		switch ( strtolower( $name ) ) {
			case 'db':
				return $this->_selectdb( $args[ 'arg1' ] );
				break;
			case 'table':
				return $this->_table( $args[ 'arg1' ] );
				break;
			case 'where':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], $args[ 'arg3' ] );
				break;
			case 'whereequal':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$eq' );
				break;
			case 'wherenot':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$ne' );
				break;
			case 'wheregreaterthan':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$gt' );
				break;
			case 'wheregreaterthanorequal':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$gte' );
				break;
			case 'wherelessthan':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$lt' );
				break;
			case 'wherelessthanorequal':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$lte' );
				break;
			case 'wherein':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$in' );
				break;
			case 'wherenotin':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$nin' );
				break;
			case 'orwhereequal':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$eq', '$or' );
				break;
			case 'orwherenot':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$ne', '$or' );
				break;
			case 'orwheregreaterthan':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$gt', '$or' );
				break;
			case 'orwheregreaterthanorequal':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$gte', '$or' );
				break;
			case 'orwherelessthan':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$lt', '$or' );
				break;
			case 'orwherelessthanorequal':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$lte', '$or' );
				break;
			case 'orwherein':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$in', '$or' );
				break;
			case 'orwherenotin':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], '$nin', '$or' );
				break;
			case 'orwhere':
				return $this->_where( $args[ 'arg1' ], $args[ 'arg2' ], $args[ 'arg3' ], '$or' );
				break;
			case 'select':
				return $this->_select( $args[ 'arg1' ] ? : [] );
				break;
			case 'orderby':
				return $this->_orderby( $args[ 'arg1' ] ? : [], $args[ 'arg2' ] ? : 'ASC' );
				break;
			case 'create':
				return $this->_insert( $args[ 'arg1' ] );
				break;
			case 'createmany':
				return $this->_insert( $args[ 'arg1' ], true );
				break;
			case 'update':
				return $this->_update( $args[ 'arg1' ] );
				break;
			case 'delete':
				return $this->_delete();
				break;
			case 'get':
				return $this->_get( $args[ 'arg1' ] );
				break;
			case 'first':
				return $this->_first();
				break;
			case 'count':
				return $this->_count();
				break;
			default:
				return $this;
				break;
		}

		return $this;
	}

	/**
	 * Start the query builder
	 *
	 * @return static
	 */
	public static function query(): self
	{
		return self::__callStatic( '', [] );
	}

	/**
	 * @param        $key
	 * @param string $operator_or_value
	 * @param string $value
	 * @param string $type
	 *
	 * @return $this
	 */
	protected function _where( $key, $value = '', $operator = '$eq', $type = '$and' )
	{
		$operator = in_array( $operator, self::ALLOWED_OPERATORS ) ? $operator : '$eq';

		if ( $key ) {
			$this->add_where_condition( $key, $value, $operator, $type );
		}

		return $this;
	}

	/**
	 * @param        $key
	 * @param        $value
	 * @param string $operator
	 * @param string $type
	 */
	protected function add_where_condition( $key, $value, $operator = '=', $type = '$and' )
	{
		if ( $this->query_where ) {
			if ( $type == '$or' && $this::$current_depth_info[ 'type' ] == '$and' ) {
				if ( $this->is_group_where ) {
					$this::$current_depth_info[ 'type' ] = '$or';
				} else {
					$this->query_where[ '$or' ] = $this->query_where[ '$and' ];
					unset( $this->query_where[ '$and' ] );
				}
			}
		} else {
			$this->query_where[ $type ] = [];
		}
		// set group where conditions
		if ( is_callable( $key ) ) {
			$is_already_in_group       = $this->is_group_where;
			$this->is_group_where      = true;
			$this::$current_depth_info = [
				'type' => '$and',
				'conditions' => [],
			];
			$key( $this );
			if ( ! $is_already_in_group ) {
				$this->is_group_where = false;
			}
			$index                         = array_keys( $this->query_where );
			$index                         = reset( $index );
			$this->query_where[ $index ][] = [
				$this::$current_depth_info[ 'type' ] => $this::$current_depth_info[ 'conditions' ],
			];
		} else {
			$condition_query = [
				$key => [
					$operator => $value,
				],
			];

			if ( $this->is_group_where ) {
				$this::$current_depth_info[ 'conditions' ][] = $condition_query;
			} else {
				$index                         = array_keys( $this->query_where );
				$index                         = reset( $index );
				$this->query_where[ $index ][] = $condition_query;
			}
		}
	}

	/**
	 * @param array  $fields
	 * @param string $order
	 *
	 * @return $this
	 */
	protected function _orderby( array $fields, $order = 'ASC' )
	{
		$_fields = [];
		foreach ( $fields as $field ) {
			$_fields[ $field ] = strtolower( $order ) == 'asc' ? 1 : -1;
		}

		$this->query_orderby = $_fields;

		return $this;
	}

	/**
	 * @param $name
	 *
	 * @return $this
	 */
	protected function _select( array $fields )
	{
		$_fields = [];
		foreach ( $fields as $field ) {
			$_fields[ $field ] = 1;
		}

		$this->query_select = $_fields;

		return $this;
	}

	/**
	 * @param        $db
	 * @param        $field1
	 * @param        $operator
	 * @param        $field2
	 * @param string $type
	 *
	 * @return $this
	 */
	protected function _join( $db, $field1, $operator, $field2, $type = 'inner join' )
	{
		$this->query_join[] = "$type $db on $field1 $operator $field2";

		return $this;
	}

	/**
	 * @param $name
	 *
	 * @return $this
	 */
	protected function _table( $name )
	{
		if ( $name )
			$this->query_table = $name;

		return $this;
	}

	/**
	 * @return bool|mixed
	 */
	protected function _first()
	{
		return $this->_get( 1, true );
	}

	/**
	 * @param int $count
	 *
	 * @return array|object|null
	 */
	protected function _get( $count = 0, $first = false )
	{
		$this->initializeDatabaseAndCollection();

		$method = $first ? 'findOne' : 'find';

		print_r( $this->query_where );

		$result = $this->mongo->$method( $this->query_where, [
			'limit' => intval( $count ),
			'projection' => $this->query_select,
			'sort' => $this->query_orderby,
		] );

		return $first ? $result : $result->toArray();
	}

	/**
	 * @return int
	 * @throws MongoCuteException
	 */
	protected function _count()
	{
		$this->initializeDatabaseAndCollection();

		return $this->mongo->countDocuments( $this->query_where );
	}

	/**
	 * @param       $name
	 * @param mixed $default
	 *
	 * @return array|false|string|null
	 */
	protected static function getEnv( $name, $default = null )
	{
		return isset( $_ENV[ $name ] ) ? $_ENV[ $name ] : $default;
	}

	/**
	 * @param array $data
	 * @param bool  $multiple
	 *
	 * @return mixed
	 * @throws MongoCuteException
	 */
	protected function _insert( array $data, $multiple = false )
	{
		$this->initializeDatabaseAndCollection();

		$call = $multiple ? 'insertMany' : 'insertOne';
		return $this->mongo->$call( $data );
	}

	/**
	 * @param array $data
	 *
	 * @return UpdateResult
	 * @throws MongoCuteException
	 */
	protected function _update( array $data ): UpdateResult
	{
		$this->initializeDatabaseAndCollection();

		return $this->mongo->updateMany( $this->query_where, [
			'$set' => $data,
		] );
	}

	/**
	 * @return DeleteResult
	 * @throws MongoCuteException
	 */
	protected function _delete(): DeleteResult
	{
		$this->initializeDatabaseAndCollection();

		return $this->mongo->deleteMany( $this->query_where );
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	protected function _selectdb( string $name )
	{
		$this->db_name = $name;
		return $this;
	}

	/**
	 * @throws MongoCuteException
	 */
	protected function initializeDatabaseAndCollection()
	{
		if ( ! $this->connected ){
			throw new MongoCuteException( 'Could not connect to database' );
		}

		if ( ! $this->db_name || ! $this->query_table ) {
			throw new MongoCuteException( 'DB name or table name not been set' );
		}

		$this->mongo = $this->mongo->selectDatabase( $this->db_name );
		$this->mongo = $this->mongo->selectCollection( $this->query_table );
	}
}