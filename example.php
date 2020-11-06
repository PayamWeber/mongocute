<?php

use MongoCute\MongoCute\QueryBuilder;

require './vendor/autoload.php';

$query = QueryBuilder::query()
	->table( 'books' )
	->whereEqual( 'lastname', 'jafari' )
	->Where( function ( QueryBuilder $query ) {
		$query->where( 'name', 'payam' );
	} )
	->select( [ 'name' ] )
	->orderby( [ 'name' ], 'asc' )
	->get();

QueryBuilder::query()->table( 'books' )->createMany( [
	[ 'name' => 'mohsen', 'lastname' => 'namjoo' ],
	[ 'name' => 'mohsen2', 'lastname' => 'namjoo2' ],
] );

var_dump( $query );