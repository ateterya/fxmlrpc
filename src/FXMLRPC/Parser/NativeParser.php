<?php
namespace FXMLRPC\Parser;

use DateTime;
use DateTimeZone;
use stdClass;
use RuntimeException;
use FXMLRPC\Value\Base64;

class NativeParser implements ParserInterface
{
    public function __construct()
    {
        if (!extension_loaded('xmlrpc')) {
            throw new RuntimeException('PHP extension ext/xmlrpc missing');
        }
    }

    public function parse($xmlString, &$isFault)
    {
        $result = xmlrpc_decode($xmlString, 'UTF-8');

        $isFault = false;

        $toBeVisited = array(&$result);
        while (isset($toBeVisited[0]) && $value = &$toBeVisited[0]) {

            switch (gettype($value)) {
                case 'object':
                    switch ($value->xmlrpc_type) {

                        case 'datetime':
                            $value = DateTime::createFromFormat(
                                'Ymd\TH:i:s',
                                $value->scalar,
                                new DateTimeZone('UTC')
                            );
                            break;

                        case 'base64':
                            if ($value->scalar !== '') {
                                $value = new Base64($value->scalar);
                                break;
                            }
                            $value = null;
                            break;
                    }
                    break;

                case 'array':
                    foreach ($value as &$element) {
                        $toBeVisited[] = &$element;
                    }
                    break;
            }

            array_shift($toBeVisited);
        }

        if (is_array($result)) {
            reset($result);
            $isFault = xmlrpc_is_fault($result);
        }

        return $result;
    }
}
