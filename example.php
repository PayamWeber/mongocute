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

var_dump( $query );