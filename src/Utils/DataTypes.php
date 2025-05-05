<?php


namespace App\Utils;


use App\Entity\DataType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

class DataTypes
{
    public static function list()
    {
        $masterApi = $_ENV['MASTER_API_URL'];
        if (!array_key_exists('dataTypes', $GLOBALS)) {
            $types = json_encode(json_decode(file_get_contents($masterApi . 'api/get/data/types'),true)['types']);
            $GLOBALS['dataTypes'] = $types;
        }else{
            $types = $GLOBALS['dataTypes'];
        }
        $serializer = new Serializer([new GetSetMethodNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);
        return new ArrayCollection($serializer->deserialize($types, 'App\Entity\DataType[]', 'json'));
    }


    public static function typeExist($type)
    {
        $id = null;
        if (is_array($type)) {
            $id = $type['id'];
        }
        if ($type instanceof DataType) {
            $id = $type->getId();
        }
        if (is_numeric($type)) {
            $id = $type;
        }

        $match = DataTypes::list()->filter(function (DataType $type) use ($id) {
            return $type->getId() === $id;
        })->first();
        return $match !== false;
    }

   
}
