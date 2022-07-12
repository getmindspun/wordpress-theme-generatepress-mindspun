<?php
declare(strict_types=1);
namespace mindspun\theme\website;

require_once( 'EddCustomFields.php' );

class Theme {
    private $version;
    private $file;

    public function __construct( string $file, string $version ) {
        $this->file = $file;
        $this->version = $version;

        new EddCustomFields( $this );
    }

    public function __get( $name ) {
        if ( substr( $name, 0, 1 ) !== '_' ) {
            return $this->$name;
        }
        throw new \Exception( 'Undefined property: ' . get_class( $this ) . '::' . $name );
    }
}
