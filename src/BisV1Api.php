<?php

namespace  kuzar;

class BisV1Api
{
    protected $defaultOption = [
        "hostname" => "avoindata.prh.fi",
        "port" => 443,
        "path" => "/bis/v1",
        "method" => "GET",
        "protocol" => "https"
    ];

    protected $option = null;

    public function __construct($option = null)
    {
        $this->option = $option;
        if (!($this->option)) $this->option = $this->defaultOption;
    }
// ================ class public exposed functions below ========================

    public static function getOptionTemplate()
    {
        return [
            "hostname" => "",
            "port" => 0,
            "path" => "",
            "method" => "",
            "protocol" => ""
        ];
    }

    public static function getQueryTemplate()
    {
        return [
            "totalResults" => "false",
            "maxResults" => "10",
            "resultsFrom" => "0",
            "name" => "",
            "businessId" => "",
            "registeredOffice" => "",
            "streetAddressPostCode" => "",
            "companyForm" => "",
            "businessLine" => "",
            "businessLineCode" => "",
            "companyRegistrationFrom" => "",
            "companyRegistrationTo" => ""
        ];
    }

    public static function getStructedReturnTemplate()
    {
        return [
            "name" => null,
            "businessId" => null,
            "companyForm" => null,
            "website" => null,
            "latestAddr" => null,
            "latestPost" => null,
            "latestCity" => null,
            "latestBusinessCode" => null,
            "latestBusinessLine" => null,
            "latestAuxiliaryNames" => null,
        ];
    }

    public function queryCompanyDetailWithParam(Array $inputObj)
    {
        if (empty($inputObj)) return [];
        if (!is_array($inputObj)) return [];

        try {
            $filtered = $this->validateAndFilterInputQueryParams($inputObj);
            $url = $this->getParamUrl($filtered);
            $jsonStr = file_get_contents($url);

            if (!$jsonStr) {
                throw new \Exception("Cannot get to url, possible 404 happend");
            }

            return json_decode(($jsonStr), 1);
        } catch (\Exception $e) {
            $this->exceptionHandler($e);
            return [];
        }
    }

    public function queryCompanyDetailWithBusinessId($inputId, $inputUrl = false)
    {
        if (empty($inputId)) return [];
        if (!is_string($inputId)) return [];

        try {
            $url = (!$inputUrl)? $this->getBusIdUrl($inputId): $inputUrl;
            $jsonStr = file_get_contents($url);

            if (!$jsonStr) {
                throw new \Exception("Cannot get to url, possible 404 happend");
            }

            return json_decode(($jsonStr), 1);
        } catch (\Exception $e) {
            $this->exceptionHandler($e);
            return [];
        }
    }

    public function getCompanyStructedDataWithParam(Array $inputObj)
    {
        $jsonObj = $this->queryCompanyDetailWithParam($inputObj);
        $resStruct = self::getStructedReturnTemplate();

        if (isset($jsonObj["results"])) {
            // if there any results? this will have three case
            // Which is 0 1 and greater that 1, 1+

            $size = count($jsonObj["results"]);

            var_dump($size);
            if ($size === 0) return [];

            if ($size === 1) {
                if (isset($jsonObj["results"][0])) return $this->processResultsArray($jsonObj["results"][0]);
            }

            if ($size > 1) {
                $response = [];
                $rs = $jsonObj["results"];
                foreach ($rs as $results){
                    $busId = $this->getBusinessIdFromUrl($results["detailsUri"]);
                    $response[] = $this->getCompanyStructedDataWithBusinessId($busId);
                }

                return $response;
            }
        }

        return [];
    }

    public function getCompanyStructedDataWithBusinessId($inputId)
    {
        $jsonObj = $this->queryCompanyDetailWithBusinessId($inputId);
        if (isset($jsonObj["results"][0])) return $this->processResultsArray($jsonObj["results"][0]);
        return [];
    }

// ==========================  class internal functions below.  =================================

    protected function exceptionHandler (\Exception $e)
    {
        echo "ERR_MSG: " . $e->getMessage() . PHP_EOL;
        echo $e->getTraceAsString();
        // var_dump($e);
    }

    protected function getBusinessIdFromUrl($url)
    {
        $arr = explode("/", $url);
        $size = count($arr);
        return $arr[$size - 1];
    }

    protected function processResultsArray($results)
    {
        $resStruct = self::getStructedReturnTemplate();
        if (isset($results)) {
            $detail = $results;
            $resStruct["name"] = $detail["name"];
            $resStruct["businessId"] = $detail["businessId"];
            $resStruct["companyForm"] = $detail["companyForm"];

            $resStruct["website"]              = $this->fetchLatestValueFromArrayWithKey("value", "registrationDate",$detail["contactDetails"]);
            $resStruct["latestAddr"]           = $this->fetchLatestValueFromArrayWithKey("street", "registrationDate",$detail["addresses"]);
            $resStruct["latestPost"]           = $this->fetchLatestValueFromArrayWithKey("postCode", "registrationDate",$detail["addresses"]);
            $resStruct["latestCity"]           = $this->fetchLatestValueFromArrayWithKey("city", "registrationDate",$detail["addresses"]);
            $resStruct["latestBusinessCode"]   = $this->fetchLatestValueFromArrayWithKey("code", "registrationDate",$detail["businessLines"]);
            $resStruct["latestBusinessLine"]   = $this->fetchLatestValueFromArrayWithKey("name", "registrationDate",$detail["businessLines"]);
            $resStruct["latestAuxiliaryNames"] = $this->fetchLatestValueFromArrayWithKey("name", "registrationDate",$detail["auxiliaryNames"]);
            return $resStruct;
        }
        return [];
    }

    protected function fetchLatestValueFromArrayWithKey ($tagetKey, $sortKey, Array $arr, $sortType = SORT_DESC)
    {
        $tempArr = array_column($arr, $sortKey);
        array_multisort($tempArr, $sortType, $arr);

        if (count($arr)) {
            if (isset($arr[0][$tagetKey])) return $arr[0][$tagetKey];
        }
        return "";
    }

    protected function getParamUrl($paramsArr = [])
    {
        $paramsStr = '';

        if (count($paramsArr)) {
            $tmp = [];
           foreach ($paramsArr as $k=>$v) {
               $tmp [] = urlencode($k) . "=" . urlencode($v);
           }

           $paramsStr = implode("&",$tmp);
        }

        return implode("",[
            $this->option["protocol"],
            "://",
            $this->option["hostname"],
            $this->option["path"],
            "?",
            $paramsStr
        ]);
    }

    protected function getBusIdUrl($businessId)
    {
        if (!$this->validateBusinessIdWithRegex($businessId)) {
            throw new \Exception("BusinessId is not Valid, Genterate Url fail");
        }

        return implode("",[
            $this->option["protocol"],
            "://",
            $this->option["hostname"],
            $this->option["path"],
            "/",
            $businessId
        ]);
    }

    protected function validateBusinessIdWithRegex($busId)
    {
        // valid business id is xxxxxxx-x format x should be number
        $regex = '/[\d]{7}-[\d]/m';
        preg_match_all($regex, $busId, $matches, PREG_SET_ORDER, 0);
        if (count($matches) === 1) return true;
        return false;
    }

    protected function validateDateWithRegex($dataStr)
    {
        // valid date is yyyy-mm-dd format
        $regex = '/^\d{4}-\d{2}-\d{2}$/m';
        preg_match_all($regex, $dataStr, $matches, PREG_SET_ORDER, 0);
        if (count($matches) === 1) {
            // check if date range valid
            $tmpStr= explode("-", $dataStr);
            return checkdate($tmpStr[1], $tmpStr[2], $tmpStr[0]);
        }
        return false;
    }

    protected function validateCompanyForm($formStr)
    {
        $companyType = ["AOY",  "OYJ",  "OY", "OK", "VOJ"];
        return in_array(strtoupper($formStr), $companyType);
    }

    protected function validateAndFilterInputQueryParams(Array $input)
    {
        $allowedKeys = ["totalResults", "maxResults", "resultsFrom",
            "name", "businessId", "registeredOffice", "streetAddressPostCode",
            "companyForm", "businessLine", "businessLineCode",
            "companyRegistrationFrom", "companyRegistrationTo",
        ];

        $filteredByKey = array_filter($input, function ($key) use ($allowedKeys) {
            return in_array($key, $allowedKeys);
        }, ARRAY_FILTER_USE_KEY);

        // Check some key value
        if (isset($filteredByKey["companyRegistrationFrom"])) {
            if (!$this->validateDateWithRegex($filteredByKey["companyRegistrationFrom"])) {
                throw  new \Exception("companyRegistrationFrom is not Valid");
            }
        }
        if (isset($filteredByKey["companyRegistrationTo"])) {
            if (!$this->validateDateWithRegex($filteredByKey["companyRegistrationTo"])) {
                throw new \Exception("companyRegistrationto is not Valid");
            }
        }
        if (isset($filteredByKey["businessId"])) {
            if (!$this->validateBusinessIdWithRegex($filteredByKey["businessId"])){
                throw new \Exception("Business Id is not valid");
            }
        }
        if (isset($filteredByKey["companyForm"])) {
            if (!$this->validateCompanyForm($filteredByKey["companyForm"])) {
                throw new \Exception("CompanyForm is not Valid");
            }
        }

        $res = $filteredByKey;
        if (count($filteredByKey)) {
            $res = array_map(function ($el) {
                // sanitize
                return htmlspecialchars(strip_tags($el));
            }, $filteredByKey);
        }

        return $res;
    }

}