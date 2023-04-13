<?php
/**
 * IXR - The Incutio XML-RPC Library
 *
 * Copyright (c) 2010, Incutio Ltd.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *  - Neither the name of Incutio Ltd. nor the names of its contributors
 *    may be used to endorse or promote products derived from this software
 *    without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * This library is based on Incutio XMLRPC library version 1.7.4 7th September 2010
 * It retains only the client code with some changes
 * Modified by Victor Dramba 2015
 *
 *  @author    Simon Willison
 *  @copyright Simon Willison
 *  @license   http://www.apache.org/licenses/LICENSE-2.0
 */
class XMLRPC_Value
{
    public $data;
    public $type;

    public function XMLRPC_Value($data, $type = false)
    {
        $this->data = $data;
        if (!$type) {
            $type = $this->calculateType();
        }
        $this->type = $type;
        if ($type == 'struct') {
            // Turn all the values in the array in to new XMLRPC_Value objects
            foreach ($this->data as $key => $value) {
                $this->data[$key] = new XMLRPC_Value($value);
            }
        }
        if ($type == 'array') {
            for ($i = 0; $i < count($this->data); ++$i) {
                $this->data[$i] = new XMLRPC_Value($this->data[$i]);
            }
        }
    }

    public function calculateType()
    {
        if ($this->data === true || $this->data === false) {
            return 'boolean';
        }
        if (is_integer($this->data)) {
            return 'int';
        }
        if (is_double($this->data)) {
            return 'double';
        }

        // Deal with IXR object types base64 and date
        if (is_object($this->data) && is_a($this->data, 'XMLRPC_Date')) {
            return 'date';
        }
        if (is_object($this->data) && is_a($this->data, 'XMLRPC_Base64')) {
            return 'base64';
        }

        // If it is a normal PHP object convert it in to a struct
        if (is_object($this->data)) {
            $this->data = get_object_vars($this->data);

            return 'struct';
        }
        if (!is_array($this->data)) {
            return 'string';
        }

        // We have an array - is it an array or a struct?
        if ($this->isStruct($this->data)) {
            return 'struct';
        } else {
            return 'array';
        }
    }

    public function getXml()
    {
        // Return XML for this value
        switch ($this->type) {
            case 'boolean':
                return '<boolean>' . (($this->data) ? '1' : '0') . '</boolean>';
                break;
            case 'int':
                return '<int>' . $this->data . '</int>';
                break;
            case 'double':
                return '<double>' . $this->data . '</double>';
                break;
            case 'string':
                return '<string>' . htmlspecialchars($this->data) . '</string>';
                break;
            case 'array':
                $return = '<array><data>\n';
                foreach ($this->data as $item) {
                    $return .= '  <value>' . $item->getXml() . '</value>\n';
                }
                $return .= '</data></array>';

                return $return;
                break;
            case 'struct':
                $return = '<struct>\n';
                foreach ($this->data as $name => $value) {
                    $return .= '  <member><name>$name</name><value>';
                    $return .= $value->getXml() . '</value></member>\n';
                }
                $return .= '</struct>';

                return $return;
                break;
            case 'date':
            case 'base64':
                return $this->data->getXml();
                break;
        }

        return false;
    }

    /**
     * Checks whether or not the supplied array is a struct or not
     *
     * @param unknown_type $array
     *
     * @return bool
     */
    public function isStruct($array)
    {
        $expected = 0;
        foreach ($array as $key => $value) {
            if ((string) $key != (string) $expected) {
                return true;
            }
            ++$expected;
        }

        return false;
    }
}

/**
 * XMLRPC_MESSAGE
 */
class XMLRPC_Message
{
    public $message;
    public $messageType;  // methodCall / methodResponse / fault
    public $faultCode;
    public $faultString;
    public $methodName;
    public $params;
    // Current variable stacks
    public $_arraystructs = [];   // The stack used to keep track of the current array/struct
    public $_arraystructstypes = []; // Stack keeping track of if things are structs or array
    public $_currentStructName = [];  // A stack as well
    public $_param;
    public $_value;
    public $_currentTag;
    public $_currentTagContents;
    // The XML parser
    public $_parser;

    public function XMLRPC_Message($message)
    {
        $this->message = &$message;
    }

    public function parse()
    {
        // first remove the XML declaration
        // merged from WP #10698 - this method avoids the RAM usage of preg_replace on very large messages
        $header = preg_replace('/<\?xml.*?\?>/', '', substr($this->message, 0, 100), 1);
        $this->message = substr_replace($this->message, $header, 0, 100);
        if (trim($this->message) == '') {
            return false;
        }
        $this->_parser = xml_parser_create();
        // Set XML parser to take the case of tags in to account
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
        // Set XML parser callback functions
        xml_set_object($this->_parser, $this);
        xml_set_element_handler($this->_parser, 'tag_open', 'tag_close');
        xml_set_character_data_handler($this->_parser, 'cdata');
        $chunk_size = 262144; // 256Kb, parse in chunks to avoid the RAM usage on very large messages

        while (true) {
            if (strlen($this->message) <= $chunk_size) {
                $final = true;
            }
            $part = substr($this->message, 0, $chunk_size);
            $this->message = substr($this->message, $chunk_size);
            if (!xml_parse($this->_parser, $part, $final)) {
                return false;
            }
            if ($final) {
                break;
            }
        }
        xml_parser_free($this->_parser);
        // Grab the error messages, if any
        if ($this->messageType == 'fault') {
            $this->faultCode = $this->params[0]['faultCode'];
            $this->faultString = $this->params[0]['faultString'];
        }

        return true;
    }

    public function tag_open($parser, $tag, $attr)
    {
        $this->_currentTagContents = '';
        $this->currentTag = $tag;
        switch ($tag) {
            case 'methodCall':
            case 'methodResponse':
            case 'fault':
                $this->messageType = $tag;
                break;
                /* Deal with stacks of arrays and structs */
            case 'data':    // data is to all intents and puposes more interesting than array
                $this->_arraystructstypes[] = 'array';
                $this->_arraystructs[] = [];
                break;
            case 'struct':
                $this->_arraystructstypes[] = 'struct';
                $this->_arraystructs[] = [];
                break;
        }
    }

    public function cdata($parser, $cdata)
    {
        $this->_currentTagContents .= $cdata;
    }

    public function tag_close($parser, $tag)
    {
        $valueFlag = false;
        switch ($tag) {
            case 'int':
            case 'i4':
                $value = (int) trim($this->_currentTagContents);
                $valueFlag = true;
                break;
            case 'double':
                $value = (float) trim($this->_currentTagContents);
                $valueFlag = true;
                break;
            case 'string':
                $value = (string) trim($this->_currentTagContents);
                $valueFlag = true;
                break;
            case 'dateTime.iso8601':
                $value = new XMLRPC_Date(trim($this->_currentTagContents));
                $valueFlag = true;
                break;
            case 'value':
                // "If no type is indicated, the type is string."
                if (trim($this->_currentTagContents) != '') {
                    $value = (string) $this->_currentTagContents;
                    $valueFlag = true;
                }
                break;
            case 'boolean':
                $value = (bool) trim($this->_currentTagContents);
                $valueFlag = true;
                break;
            case 'base64':
                $value = base64_decode($this->_currentTagContents);
                $valueFlag = true;
                break;
                /* Deal with stacks of arrays and structs */
            case 'data':
            case 'struct':
                $value = array_pop($this->_arraystructs);
                array_pop($this->_arraystructstypes);
                $valueFlag = true;
                break;
            case 'member':
                array_pop($this->_currentStructName);
                break;
            case 'name':
                $this->_currentStructName[] = trim($this->_currentTagContents);
                break;
            case 'methodName':
                $this->methodName = trim($this->_currentTagContents);
                break;
        }

        if ($valueFlag) {
            if (count($this->_arraystructs) > 0) {
                // Add value to struct or array
                if ($this->_arraystructstypes[count($this->_arraystructstypes) - 1] == 'struct') {
                    // Add to struct
                    $this->_arraystructs[count($this->_arraystructs) - 1][$this->_currentStructName[count($this->_currentStructName) - 1]] = $value;
                } else {
                    // Add to array
                    $this->_arraystructs[count($this->_arraystructs) - 1][] = $value;
                }
            } else {
                // Just add as a paramater
                $this->params[] = $value;
            }
        }
        $this->_currentTagContents = '';
    }
}

/**
 * XMLRPC_Request
 */
class XMLRPC_Request
{
    public $method;
    public $args;
    public $xml;

    public function XMLRPC_Request($method, $args)
    {
        $this->method = $method;
        $this->args = $args;
        $this->xml = <<<EOD
<?xml version='1.0'?>
<methodCall>
<methodName>{$this->method}</methodName>
<params>

EOD;
        foreach ($this->args as $arg) {
            $this->xml .= '<param><value>';
            $v = new XMLRPC_Value($arg);
            $this->xml .= $v->getXml();
            $this->xml .= '</value></param>\n';
        }
        $this->xml .= '</params></methodCall>';
    }

    public function getLength()
    {
        return strlen($this->xml);
    }

    public function getXml()
    {
        return $this->xml;
    }
}

class XMLRPC_LibraryMissingException extends Exception
{
}

/**
 * XMLRPC_Client
 */
class XMLRPC_Client
{
    public $url;
    public $response;
    public $message = false;
    public $debug = false;
    public $timeout;

    // Storage place for an error message
    public $error = false;

    /*  public function XMLRPC_Client($url, $timeout = 15)
     {
         if (!$this->httpsWrapperEnabled() && !function_exists('curl_init')) {
             throw new XMLRPC_LibraryMissingException('You need either php_curl extension or php_openssl and allow_url_fopen=on');
         }
         if (substr($url, 0, 8) !== 'https://') {
             throw new Exception('The URL must begin with https://');
         }

         $this->url = $url;
         $this->timeout = $timeout;
     } */

    public function __construct($url, $timeout = 15)
    {
        if (!$this->httpsWrapperEnabled() && !function_exists('curl_init')) {
            throw new XMLRPC_LibraryMissingException('You need either php_curl extension or php_openssl and allow_url_fopen=on');
        }
        if (substr($url, 0, 8) !== 'https://') {
            throw new Exception('The URL must begin with https://');
        }

        $this->url = $url;
        $this->timeout = $timeout;
    }

    private function httpsWrapperEnabled()
    {
        return in_array('https', stream_get_wrappers()) && extension_loaded('openssl');
    }

    public function query()
    {
        $args = func_get_args();
        $method = array_shift($args);
        $request = new XMLRPC_Request($method, $args);
        $length = $request->getLength();
        $xml = $request->getXml();

        $header = 'Content-type: text/xml\r\nContent-length: $length\r\n';
        // choose transport
        /*if ($this->httpsWrapperEnabled()) {
            $opts = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => $header,
                    'content' => $xml
                ),
                'ssl' => array(
                    'cafile' => dirname(__FILE__) . '/cacert.pem',
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                )
            );

            $contents = file_get_contents($this->url, false, stream_context_create($opts));
        } else { */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: text/xml']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $contents = curl_exec($ch);
        curl_close($ch);
        // }

        // Now parse what we've got back
        $this->message = new XMLRPC_Message($contents);
        if (!$this->message->parse()) {
            // XML error
            $this->error = new XMLRPC_Error(-32700, 'parse error. not well formed');

            return false;
        }

        // Is the message a fault?
        if ($this->message->messageType == 'fault') {
            $this->error = new XMLRPC_Error($this->message->faultCode, $this->message->faultString);

            return $this->error;
        }

        // Message must be OK
        return true;
    }

    public function getResponse()
    {
        // methodResponses can only have one param - return that
        return $this->message->params[0];
    }

    public function isError()
    {
        return is_object($this->error);
    }

    public function getErrorCode()
    {
        return $this->error->code;
    }

    public function getErrorMessage()
    {
        return $this->error->message;
    }
}

/**
 * XMLRPC_Error
 */
class XMLRPC_Error
{
    public $code;
    public $message;

    public function XMLRPC_Error($code, $message)
    {
        $this->code = $code;
        $this->message = htmlspecialchars($message);
    }

    public function getXml()
    {
        $xml = <<<EOD
<methodResponse>
  <fault>
    <value>
      <struct>
        <member>
          <name>faultCode</name>
          <value><int>{$this->code}</int></value>
        </member>
        <member>
          <name>faultString</name>
          <value><string>{$this->message}</string></value>
        </member>
      </struct>
    </value>
  </fault>
</methodResponse>

EOD;

        return $xml;
    }
}

/**
 * XMLRPC_Date
 */
class XMLRPC_Date
{
    public $year;
    public $month;
    public $day;
    public $hour;
    public $minute;
    public $second;
    public $timezone;

    public function XMLRPC_Date($time)
    {
        // $time can be a PHP timestamp or an ISO one
        if (is_numeric($time)) {
            $this->parseTimestamp($time);
        } else {
            $this->parseIso($time);
        }
    }

    public function parseTimestamp($timestamp)
    {
        $this->year = date('Y', $timestamp);
        $this->month = date('m', $timestamp);
        $this->day = date('d', $timestamp);
        $this->hour = date('H', $timestamp);
        $this->minute = date('i', $timestamp);
        $this->second = date('s', $timestamp);
        $this->timezone = '';
    }

    public function parseIso($iso)
    {
        $this->year = substr($iso, 0, 4);
        $this->month = substr($iso, 4, 2);
        $this->day = substr($iso, 6, 2);
        $this->hour = substr($iso, 9, 2);
        $this->minute = substr($iso, 12, 2);
        $this->second = substr($iso, 15, 2);
        $this->timezone = substr($iso, 17);
    }

    public function getIso()
    {
        return $this->year . $this->month . $this->day . 'T' . $this->hour . ':' . $this->minute . ':' . $this->second . $this->timezone;
    }

    public function getXml()
    {
        return '<dateTime.iso8601>' . $this->getIso() . '</dateTime.iso8601>';
    }

    public function getTimestamp()
    {
        return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
    }
}

/**
 * XMLRPC_Base64
 */
class XMLRPC_Base64
{
    public $data;

    public function XMLRPC_Base64($data)
    {
        $this->data = $data;
    }

    public function getXml()
    {
        return '<base64>' . base64_encode($this->data) . '</base64>';
    }
}
