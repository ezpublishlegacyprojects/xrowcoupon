<?php

include_once( "kernel/classes/ezdatatype.php" );

define( "EZ_DATATYPESTRING_COUPON", "ezcoupon" );
define( 'EZ_DATATYPESTRING_COUPON_DEFAULT', 'data_int1' );
define( 'EZ_DATATYPESTRING_COUPON_DEFAULT_EMTPY', 0 );
define( 'EZ_DATATYPESTRING_COUPON_DEFAULT_CURRENT_COUPON', 1 );

define( 'EZ_DATATYPE_COUPON_DISCOUNT_TYPE_PERCENT', 0 );
define( 'EZ_DATATYPE_COUPON_DISCOUNT_TYPE_FLAT', 1 );
define( 'EZ_DATATYPE_COUPON_DISCOUNT_TYPE_FREE_SHIPPING', 2 );

include_once( "lib/ezlocale/classes/ezdate.php" );
include_once( 'extension/coupon/classes/xrowcoupon.php' );

class ezCouponType extends eZDataType
{
    function ezCouponType()
    {
        $this->eZDataType( EZ_DATATYPESTRING_COUPON, ezi18n( 'kernel/classes/datatypes', "Coupon", 'Datatype name' ),
                           array( 'serialize_supported' => true ) );
    }


    function validateDateTimeHTTPInput( $day, $month, $year, &$contentObjectAttribute )
    {
        include_once( 'lib/ezutils/classes/ezdatetimevalidator.php' );
        $state = eZDateTimeValidator::validateDate( $day, $month, $year );
        if ( $state == EZ_INPUT_VALIDATOR_STATE_INVALID )
        {
            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                 'Date is not valid.' ) );
            return EZ_INPUT_VALIDATOR_STATE_INVALID;
        }
        return $state;
    }
    /*!
     Validates the input and returns true if the input was
     valid for this datatype.
    */
    function validateObjectAttributeHTTPInput( &$http, $base, &$contentObjectAttribute )
    {
        $return = EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
        if ( $http->hasPostVariable( $base . '_coupon_year_' . $contentObjectAttribute->attribute( 'id' ) ) and
             $http->hasPostVariable( $base . '_coupon_month_' . $contentObjectAttribute->attribute( 'id' ) ) and
             $http->hasPostVariable( $base . '_coupon_day_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $year  = $http->postVariable( $base . '_coupon_year_' . $contentObjectAttribute->attribute( 'id' ) );
            $month = $http->postVariable( $base . '_coupon_month_' . $contentObjectAttribute->attribute( 'id' ) );
            $day   = $http->postVariable( $base . '_coupon_day_' . $contentObjectAttribute->attribute( 'id' ) );
            $classAttribute =& $contentObjectAttribute->contentClassAttribute();

            if ( $year == '' or $month == '' or $day == '' )
            {
                if ( !( $year == '' and $month == '' and $day == '' ) or
                     $contentObjectAttribute->validateIsRequired() )
                {
                    $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                         'Missing date input.' ) );
                    $return = EZ_INPUT_VALIDATOR_STATE_INVALID;
                }
            }
            else
            {
                if ( $this->validateDateTimeHTTPInput( $day, $month, $year, $contentObjectAttribute ) == EZ_INPUT_VALIDATOR_STATE_INVALID )
                    $return = EZ_INPUT_VALIDATOR_STATE_INVALID;
                $date = new eZDate();
                $date->setMDY( $month, $day, $year );
            }
        }

        if ( $http->hasPostVariable( $base . '_coupon_till_year_' . $contentObjectAttribute->attribute( 'id' ) ) and
             $http->hasPostVariable( $base . '_coupon_till_month_' . $contentObjectAttribute->attribute( 'id' ) ) and
             $http->hasPostVariable( $base . '_coupon_till_day_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $year  = $http->postVariable( $base . '_coupon_till_year_' . $contentObjectAttribute->attribute( 'id' ) );
            $month = $http->postVariable( $base . '_coupon_till_month_' . $contentObjectAttribute->attribute( 'id' ) );
            $day   = $http->postVariable( $base . '_coupon_till_day_' . $contentObjectAttribute->attribute( 'id' ) );
            $classAttribute =& $contentObjectAttribute->contentClassAttribute();

            if ( $year == '' or $month == '' or $day == '' )
            {
                if ( !( $year == '' and $month == '' and $day == '' ) or
                     $contentObjectAttribute->validateIsRequired() )
                {
                    $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                         'Missing date input.' ) );
                    $return = EZ_INPUT_VALIDATOR_STATE_INVALID;
                }
            }
            else
            {
                if ( $this->validateDateTimeHTTPInput( $day, $month, $year, $contentObjectAttribute ) == EZ_INPUT_VALIDATOR_STATE_INVALID )
                    $return = EZ_INPUT_VALIDATOR_STATE_INVALID;
                $date2 = new eZDate();
                $date2->setMDY( $month, $day, $year );
            }
        }
        if ( is_object( $date ) and is_object( $date2 ) and ( $date->timeStamp() > $date2->timeStamp() or time() > $date2->timeStamp() ) )
        {
            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                 'Expiry date incorrect.' ) );
            $return = EZ_INPUT_VALIDATOR_STATE_INVALID;
        }
        if ( $http->hasPostVariable( $base . "_coupon_discount_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            $data = $http->postVariable( $base . "_coupon_discount_" . $contentObjectAttribute->attribute( "id" ) );

            include_once( 'lib/ezlocale/classes/ezlocale.php' );
            $locale =& eZLocale::instance();
            $data = $locale->internalCurrency( $data );
            $classAttribute =& $contentObjectAttribute->contentClassAttribute();
            if( $contentObjectAttribute->validateIsRequired() && ( $data == "" or  $data <= 0 ) )
            {
                $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                 'No discount set.' ) );
                $return = EZ_INPUT_VALIDATOR_STATE_INVALID;
            }
            if ( !preg_match( "#^[0-9]+(.){0,1}[0-9]{0,2}$#", $data ) )
            {
                $return = EZ_INPUT_VALIDATOR_STATE_INVALID;

                $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                 'Invalid discount.' ) );
            }
            if ( preg_match( "#^[0-9]+(.){0,1}[0-9]{0,2}$#", $data ) and (int)$http->postVariable( $base . "_coupon_discount_type_" . $contentObjectAttribute->attribute( "id" ) ) == EZ_DATATYPE_COUPON_DISCOUNT_TYPE_PERCENT )
            {
                if( !( $data > 0 and $data < 100 ) )
                {
                   $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                 'Give a discount value between nero and 100.' ) );
                   $return = EZ_INPUT_VALIDATOR_STATE_INVALID;
                }
            }
        }
        if ( $http->hasPostVariable( $base . '_coupon_code_' . $contentObjectAttribute->attribute( 'id' ) ) and $http->postVariable( $base . '_coupon_code_' . $contentObjectAttribute->attribute( 'id' ) ) == "" )
        {
            $return = EZ_INPUT_VALIDATOR_STATE_INVALID;
            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                 'Invalid coupon code.' ) );
        }
        return $return;
    }

    /*!
     Fetches the http post var integer input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( &$http, $base, &$contentObjectAttribute )
    {
        if ( $http->hasPostVariable( $base . '_coupon_year_' . $contentObjectAttribute->attribute( 'id' ) ) and
             $http->hasPostVariable( $base . '_coupon_month_' . $contentObjectAttribute->attribute( 'id' ) ) and
             $http->hasPostVariable( $base . '_coupon_day_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {

            $year  = $http->postVariable( $base . '_coupon_year_' . $contentObjectAttribute->attribute( 'id' ) );
            $month = $http->postVariable( $base . '_coupon_month_' . $contentObjectAttribute->attribute( 'id' ) );
            $day   = $http->postVariable( $base . '_coupon_day_' . $contentObjectAttribute->attribute( 'id' ) );
            $date = new eZDate();
            $contentClassAttribute =& $contentObjectAttribute->contentClassAttribute();

            if ( ( $year == '' and $month == '' and $day == '' ) or
                 !checkdate( $month, $day, $year ) or
                 $year < 1970 )
            {
                $date->setTimeStamp( 0 );
            }
            else
            {
                $date->setMDY( $month, $day, $year );
            }
        }
        if ( $http->hasPostVariable( $base . '_coupon_till_year_' . $contentObjectAttribute->attribute( 'id' ) ) and
             $http->hasPostVariable( $base . '_coupon_till_month_' . $contentObjectAttribute->attribute( 'id' ) ) and
             $http->hasPostVariable( $base . '_coupon_till_day_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {

            $year  = $http->postVariable( $base . '_coupon_till_year_' . $contentObjectAttribute->attribute( 'id' ) );
            $month = $http->postVariable( $base . '_coupon_till_month_' . $contentObjectAttribute->attribute( 'id' ) );
            $day   = $http->postVariable( $base . '_coupon_till_day_' . $contentObjectAttribute->attribute( 'id' ) );
            $datetill = new eZDate();
            $contentClassAttribute =& $contentObjectAttribute->contentClassAttribute();

            if ( ( $year == '' and $month == '' and $day == '' ) or
                 !checkdate( $month, $day, $year ) or
                 $year < 1970 )
            {
                $datetill->setTimeStamp( 0 );
            }
            else
            {
                $datetill->setMDY( $month, $day, $year );
            }
        }
        $type = (int)$http->postVariable( $base . '_coupon_discount_type_' . $contentObjectAttribute->attribute( 'id' ) );

        $discount = $http->postVariable( $base . '_coupon_discount_' . $contentObjectAttribute->attribute( 'id' ) );
        $discount = floatval( $discount );
        $contentObjectAttribute->setAttribute( 'data_text', strtoupper( $http->postVariable( $base . '_coupon_code_' . $contentObjectAttribute->attribute( 'id' ) ) ). ";" . $date->timeStamp().";".$datetill->timeStamp() );
        $contentObjectAttribute->setAttribute( 'data_float', $discount );
        $contentObjectAttribute->setAttribute( 'data_int', $type );
        return true;
    }

    /*!
     Returns the content.
    */
    function &objectAttributeContent( &$contentObjectAttribute )
    {
        $tmp = $contentObjectAttribute->attribute( 'data_text' );
        $tmparray = split(";",$tmp,3);
        $date = new eZDate( );
        $stamp = $tmparray[1];
        $date->setTimeStamp( $stamp );
        $date2 = new eZDate( );
        $stamp = $tmparray[2];
        $date2->setTimeStamp( $stamp );
        $coupon = array( 'from' => $date, 'till' => $date2, 'discount' => $contentObjectAttribute->attribute( 'data_float' ), 'discount_type' => $contentObjectAttribute->attribute( 'data_int' ), 'code' => $tmparray[0] );
        return $coupon;
    }

    /*!
     Set class attribute value for template version
    */
    function initializeClassAttribute( &$classAttribute )
    {
        if ( $classAttribute->attribute( EZ_DATATYPESTRING_COUPON_DEFAULT ) == null )
            $classAttribute->setAttribute( EZ_DATATYPESTRING_COUPON_DEFAULT, 0 );
        $classAttribute->store();
    }

    /*!
     Sets the default value.
    */
    function initializeObjectAttribute( &$contentObjectAttribute, $currentVersion, &$originalContentObjectAttribute )
    {
        if ( $currentVersion != false )
        {
            $dataInt = $originalContentObjectAttribute->attribute( "data_int" );
            $contentObjectAttribute->setAttribute( "data_int", $dataInt );
        }
        else
        {
            $contentClassAttribute =& $contentObjectAttribute->contentClassAttribute();
            $defaultType = $contentClassAttribute->attribute( EZ_DATATYPESTRING_COUPON_DEFAULT );
            if ( $defaultType == 1 )
                $contentObjectAttribute->setAttribute( "data_int", mktime() );
        }
    }

    function fetchClassAttributeHTTPInput( &$http, $base, &$classAttribute )
    {
        $default = $base . "_ezcoupon_default_" . $classAttribute->attribute( 'id' );
        if ( $http->hasPostVariable( $default ) )
        {
            $defaultValue = $http->postVariable( $default );
            $classAttribute->setAttribute( EZ_DATATYPESTRING_COUPON_DEFAULT,  $defaultValue );
        }
        return true;
    }

    /*!
     \reimp
    */
    function isIndexable()
    {
        return true;
    }

    /*!
     Returns the meta data used for storing search indeces.
    */
    function metaData( $contentObjectAttribute )
    {
        $array = $contentObjectAttribute->objectAttributeContent( );
        $retVal = $array['code'];
        return $retVal;
    }

    /*!
     Returns the date.
    */
    function title( &$contentObjectAttribute )
    {
        $locale =& eZLocale::instance();
        $array = $contentObjectAttribute->objectAttributeContent( );
        $retVal = $array['code'];
        return $retVal;
    }

    function hasObjectAttributeContent( &$contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( "data_float" ) != 0;
    }

    /*!
     \reimp
    */
    function sortKey( &$contentObjectAttribute )
    {
        return (int)$contentObjectAttribute->attribute( 'data_float' );
    }

    /*!
     \reimp
    */
    function sortKeyType()
    {
        return 'float';
    }
}

eZDataType::register( EZ_DATATYPESTRING_COUPON, "ezcoupontype" );

?>
