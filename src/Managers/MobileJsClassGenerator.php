<?php

namespace App\Managers;

use ReflectionAttribute;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

class MobileJsClassGenerator
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var PropertyInfoExtractor */
    private $propertyInfo;

    private $root = 'App\Entity\\';
    private $entity = '';
    private $classes = [];
    private $classname = '';
    private $avoid = ['id'];
    private $forced = [
        ['name' => 'uuid', 'type' => 'string', 'nullable' => true]
    ];
    private $content = [];
    private $imports = [];
    private $properties = [];
    private $propertiesHandled = [];
    private $declaration = [];
    private $constructor = [];
    private $form = [];
    private $dateFormats = ['DateTime'];

    private $debug = false;

    private $mode = 'mobile';
    private $entityManager = 'entityManager';

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        // $this->entityManager = $this->mode === 'mobile' ? 'entityManager' : 'store.state.entityManager';
    }

    public function setMobile()
    {
        $this->mode = 'mobile';
        $this->entityManager = 'entityManager';
    }

    public function setWeb()
    {
        $this->mode = 'web';
        $this->entityManager = 'store.state.entityManager';
    }

    public function generate($entityName)
    {
        $this->debug ? dump('GENERATING ENTITY ' . $entityName) : null;
        $this->entity = $entityName;
        $this->classname = $this->root . $entityName;
        if (class_exists($this->classname)) {
            if (in_array($entityName, $this->classes, false)) {
                return;
            }
            $this->classes[] = $entityName;
            $className = 'App\Entity\\' . $entityName;
            $entity = new $className();
            $reflect = new \ReflectionClass($entity);
            $properties = $reflect->getProperties();
            $this->imports[$entityName] = [];
            foreach ($properties as $key => $property) {
                $attributes = $property->getAttributes();

                $list = false;
                $type = null;
                $this->debug ? dump('-----------Property ' . $property->name . '--------------') : null;
                // $this->debug ?  dump($attributes) : null;

                if ($attributes === []) {
                    continue;
                }
                if (count($attributes) > 0) {
                    $relation = $attributes[0];
                }
                $this->debug ? dump($relation->getName()) : null;
                if ($relation->getName() === ManyToMany::class) {

                    $type = $relation->getArguments()['targetEntity'];
                    // dd($relation);
                    $mappedBy = null;
                    $inversedBy = null;
                    if (isset($relation->getArguments()['mappedBy'])) {
                        $mappedBy = $relation->getArguments()['mappedBy'];
                    }
                    if (isset($relation->getArguments()['inversedBy'])) {
                        $inversedBy = $relation->getArguments()['inversedBy'];
                    }
                    // if (!$inversedBy) {
                    //     throw new \Exception('inversedby missing on property ' . $property->name . ' on class ' . $entityName);
                    // }
                    $this->generateCollection($property, $entityName, str_replace('App\Entity\\', '', $type), $mappedBy, $inversedBy);
                }
                if ($relation->getName() === OneToOne::class) {
                    $targetEntity = null;
                    if (array_key_exists('targetEntity', $relation->getArguments())) {
                        $targetEntity = $relation->getArguments()['targetEntity'];
                    }
                    if (!$targetEntity) {
                        if ($property->getType() instanceof \ReflectionNamedType) {
                            $targetEntity = $property->getType()->getName();
                        }
                    }
                    if (!$targetEntity) {
                        throw new \Exception('La propriété ' . $property->name . ' de la classe ' . $entityName . ' (' . $relation->getName() . ') ne renseigne pas son attribut "targetEntity". A quelle entité est elle liée ? ');
                    }
                    $this->generateOneToOne($property->name, $entityName, $relation);
                    $this->generate(str_replace('App\Entity\\', '', $targetEntity));
                }
                if ($relation->getName() === ManyToOne::class) {
                    $targetEntity = null;
                    if (array_key_exists('targetEntity', $relation->getArguments())) {
                        $targetEntity = $relation->getArguments()['targetEntity'];
                    }
                    if (!$targetEntity) {
                        if ($property->getType() instanceof \ReflectionNamedType) {
                            $targetEntity = $property->getType()->getName();
                        }
                    }
                    if (!$targetEntity) {
                        throw new \Exception('La propriété ' . $property->name . ' de la classe ' . $entityName . ' (' . $relation->getName() . ') ne renseigne pas son attribut "targetEntity". A quelle entité est elle liée ? ');
                    }
                    $this->generateManyToOne($property, $entityName, $targetEntity, $relation);
                    $this->generate(str_replace('App\Entity\\', '', $targetEntity));
                }
                if ($relation->getName() === OneToMany::class) {
                    $type = $relation->getArguments()['targetEntity'];
                    $mappedBy = $relation->getArguments()['mappedBy'];
                    $this->generateCollection($property, $entityName, str_replace('App\Entity\\', '', $type), $mappedBy);
                    $list = true;
                }
                foreach ($attributes as $attribute) {
                    $args = $attribute->getArguments();
                    if (!array_key_exists('type', $args)) {
                        $args['type'] = 'string';
                    }
                    if ($attribute->getName() === Column::class) {
                        // dump($args);
                        $type = $args['type'];
                        $nullable = array_key_exists('nullable', $args) && $args['nullable'] === true ? true : false;

                        $this->form[$entityName][] = $property->name . ':[null' . ($nullable ? '' : ',Validators.required') . '],';
                        switch ($type) {
                            case 'integer':
                            case 'float':
                            case 'smallint':
                            case 'bigint':
                            case 'decimal':
                            {
                                $this->generateNumber($property->name, $entityName, $attribute);
                                break;
                            }
                            case 'string':
                            case 'text':
                            case 'uuid':
                            {
                                $this->generateString($property->name, $entityName, $attribute);
                                break;
                            }
                            case 'datetime':
                            case 'date':
                            case 'date_immutable':
                            case 'datetime_immutable':
                            case 'time':
                            {
                                $this->generateDate($property, $entityName, $attribute);
                                break;
                            }
                            case 'boolean':
                            {
                                $this->generateBool($property->name, $entityName, $attribute);
                                break;
                            }
                            case 'json':
                            case 'json_array':
                            case 'array':
                            {
                                $this->generateArray($property->name, $entityName);
                                break;
                            }
                            default:
                            {
                                if ($property->name === 'slug') {
                                    $this->generateString($property->name, $entityName, $attribute);
                                    break;
                                }
                                // dump($entityName . '->' . $property->name . ' => ' . $type . ' not handled');
                            }
                        }
                    }
                }

                if ($relation->getName() === Id::class) {
                    // dd($relation);
                    // $type = $docInfos[2]->type;
                }
                if (!$property->name === 'slug' && !$type) {
                    dump($property);
                    dd($attributes);
                }

                //    dump($property->name . ' => ' . $type);
            }

            //            $this->addForced($entityName);
            $content = $this->finalize($entityName);
            $this->content[$entityName] = $content;
            return $this->content;
        }
    }

    private function isMobile()
    {
        return $this->mode === 'mobile' ? true : false;
    }


    private $folder = __DIR__ . './../../jsClasses/react/';

    public function finalize($entity)
    {

        $content = $this->imports[$entity] ?? [];
        array_unshift($content, '/* eslint-disabled */');

        if ($this->isMobile()) {
            $content[] = 'import ' . $entity . 'Extend from "./extends/' . $entity . 'Extend";';
            $content[] = 'import { entityManager } from "../services/EntityManager";';
            $content[] = 'import { helper } from "../services/Helper";';
        } else {
            $content[] = 'import ' . $entity . 'Extend from "@/entity/extends/' . $entity . 'Extend";';
            $content[] = 'import store from "@/store";';
            $content[] = 'import { helper } from "@/services/Helper";';
        }
        $content[] = '';
        $content[] = 'export default class ' . $entity . ' extends ' . $entity . 'Extend {';
        $content[] = '';
        $content[] = '';
        // $content[] = 'public generatedId = helper.generateId()';
        //    dd($this->declaration[$entity]);
        $content = array_merge($content, $this->declaration[$entity]);
        $content[] = '';
        $content[] = '  constructor (object?: any) {';
        $content[] = '      super(object)';
        $content[] = '      if(object){';
        $content = array_merge($content, $this->constructor[$entity]);
        $content[] = '          ' . $this->entityManager . '.persist(this)';
        $content[] = '      }';
        // $content[] = '      this.id = helper.empty(this.id) ? helper.generateId() : this.id';
        $content[] = '      this.postConstruct()';
        $content[] = '  }';
        $content[] = '';
        if (array_key_exists($entity, $this->getters)) {
            $content = array_merge($content, $this->getters[$entity]);
        }
        $content[] = '}';
        //        dd($this->form);
        $dir = $this->folder;

        try {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        } catch (\Throwable $e) {
        }
        $root = $dir . $entity . '.ts';
        $handle = fopen($root, 'w+');
        fclose($handle);
        foreach ($content as $item) {
            file_put_contents($root, $item . PHP_EOL, FILE_APPEND);
        }
        $this->generateExtends($entity);
        return $content;
    }

    public function generateExtends($entity)
    {
        $content[] = '/* eslint-disabled */';
        $content[] = '';
        $content[] = 'export default class ' . $entity . 'Extend {';
        $content[] = '';
        $content[] = '';
        $content[] = '  constructor (object?: any) {';

        $content[] = '  }';
        $content[] = '';
        $content[] = '  postConstruct () {';
        $content[] = '  }';
        $content[] = '}';

        $dir = $this->folder . 'extends/';

        try {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        } catch (\Throwable $e) {
        }
        $root = $dir . $entity . 'Extend.ts';
        $handle = fopen($root, 'w+');
        fclose($handle);
        foreach ($content as $item) {
            file_put_contents($root, $item . PHP_EOL, FILE_APPEND);
        }
        return $content;
    }

    private function addForced($entityName)
    {
        foreach ($this->forced as $item) {
            $this->declaration[$entityName][] = 'public ' . $item['name'] . ($item['nullable'] ? '?' : '') . ': ' . $item['type'] . ';';
            $this->constructor[$entityName][] = '       this.' . $item['name'] . '= object.' . $item['name'] . ';';
        }
    }

    private function generateCollection($property, $entityName, $class, $mappedBy, $inversed = null)
    {
        if ($entityName === $class) {
            return;
        }
        $class = str_replace('App\Entity\\', '', $class);
        $import = 'import ' . $class . ' from "./' . $class . '";';
        if (!in_array($import, $this->imports[$entityName], true)) {
            $this->imports[$entityName][] = $import;
        }

        $declaration = 'public ' . $property->name . ': Array<' . $class . '> = [];';
        if (!in_array($declaration, $this->declaration[$entityName], true)) {
            $this->declaration[$entityName][] = 'public ' . $property->name . 'Ids: string[] = [];';
        }
        if (!array_key_exists($entityName, $this->properties)) {
            $this->properties[$entityName] = [];
        }
        if (!in_array($property->name, $this->properties[$entityName], true)) {
            $this->constructor[$entityName][] = '       if(object.' . $property->name . 'Ids){';
            $this->constructor[$entityName][] = '               this.' . $property->name . 'Ids= object.' . $property->name . 'Ids';
            $this->constructor[$entityName][] = '       }';
            $this->constructor[$entityName][] = '       if(object.' . $property->name . '){';
            $this->constructor[$entityName][] = '           object.' . $property->name . '.forEach((occ: any)=>{';

            $this->constructor[$entityName][] = '   if(typeof occ === "string"){';
            $this->constructor[$entityName][] = '       const found = ' . $this->entityManager . '.get(occ, "' . $class . '")';
            $this->constructor[$entityName][] = '       if (found && typeof found === "object") {';
            $this->constructor[$entityName][] = '               this.' . $property->name . 'Ids.push(found.id);';
            $this->constructor[$entityName][] = '       }else{';
            $this->constructor[$entityName][] = '               this.' . $property->name . 'Ids.push(occ);';
            $this->constructor[$entityName][] = '       }';
            $this->constructor[$entityName][] = '   }else{';

            $this->constructor[$entityName][] = '               let ' . strtolower($class) . '= occ instanceof ' . $class . '? occ :   new ' . $class . '(occ);';
            $this->constructor[$entityName][] = '       if (' . strtolower($class) . ' && !(' . strtolower($class) . ' instanceof ' . $class . ')) {';
            $this->constructor[$entityName][] = '            ' . strtolower($class) . ' = new ' . $class . '(' . strtolower($class) . ')';
            $this->constructor[$entityName][] = '       }';
            $this->generateGetters($class, $property, $entityName, $mappedBy, $inversed);
            $this->constructor[$entityName][] = '               ' . $this->entityManager . '.persist(' . strtolower($class) . ')';
            //            $this->constructor[$entityName][] = '               ' . strtolower($class) . '.' . $mappedBy . ' = this ;';
            $this->constructor[$entityName][] = '               this.' . $property->name . 'Ids.push(' . strtolower($class) . '.id);';
            $this->constructor[$entityName][] = '       }';
            $this->constructor[$entityName][] = '           });';
            $this->constructor[$entityName][] = '       }';
            $this->properties[$entityName][] = $property->name;
        }
        $this->generate($class);
    }

    private $getters = [];

    private function generateGetters($class, $property, $entityName, $mappedBy, $inversedBy)
    {

        $this->getters[$entityName][] = '';
        $this->getters[$entityName][] = 'get ' . $property->name . '() {';

//        if (!$mappedBy && !$inversedBy) {
//            dump($inversedBy);
//            dump($mappedBy);
//            dd($property);
//            throw new \Exception('Missing mappedBy on property ' . $property . ' of class ' . $entityName);
//        }
        $this->getters[$entityName][] = "const rawData = " . $this->entityManager . ".get(this." . $property->name . "Ids,'" . strtolower($class) . "') ?? []";
        $this->getters[$entityName][] = "const formattedData: " . $class . "[] = []";
        $this->getters[$entityName][] = "rawData.forEach((data:any)=>{";
        $this->getters[$entityName][] = "let occ = data";
        $this->getters[$entityName][] = 'if(!(data instanceof ' . $class . ')){';
        $this->getters[$entityName][] = "occ = new " . $class . " (data)";
        $this->getters[$entityName][] = "}";
        $this->getters[$entityName][] = "   formattedData.push(occ)";
        $this->getters[$entityName][] = "})";

        $this->getters[$entityName][] = "const relations = entityManager.getRelations('" . strtolower($class) . "', '" . strtolower($entityName) . "Id');";
        $this->getters[$entityName][] = "relations.forEach((data: any) => {";
        $this->getters[$entityName][] = "let occ = data;";
        $this->getters[$entityName][] = "const exist = formattedData.find((s: " . $class . ") => {";
        $this->getters[$entityName][] = "return s.id === occ.id;";
        $this->getters[$entityName][] = "});";
        $this->getters[$entityName][] = "if (!exist) {";

        $this->getters[$entityName][] = "if (!(data instanceof " . $class . ")) {";
        $this->getters[$entityName][] = "occ = new " . $class . "(data);";
        $this->getters[$entityName][] = "occ = new " . $class . "(data);";
        $this->getters[$entityName][] = "}";
        $this->getters[$entityName][] = "formattedData.push(occ);";
        $this->getters[$entityName][] = "}";
        $this->getters[$entityName][] = "});";
        $this->getters[$entityName][] = "return formattedData";
        // $this->getters[$entityName][] = 'return;';
        $this->getters[$entityName][] = '}';
        $this->getters[$entityName][] = '';
    }

    private function generateManyToOneGetter($class, $property, $entityName, ReflectionAttribute $attribute)
    {
        $target = null;
        $inverse = null;
        if (array_key_exists('targetEntity', $attribute->getArguments())) {
            $target = $attribute->getArguments()['targetEntity'];
        }
        if(!$target){
            $target = $property->getType()->getName();
        }
        if (array_key_exists('inversedBy', $attribute->getArguments())) {
            $inverse = $attribute->getArguments()['inversedBy'];
        }

//        if(!$inverse){
//            dump('no inverse found');
//            dump($attribute);
//            dd($property);
////            $inverse = $property->getType()->getName();
//        }
        // dd($target);
        // dd($property);
        $c = str_replace('App\Entity\\', '', $class);
        // dump($class, $property, $entityName);
        $this->getters[$entityName][] = '';
        $this->getters[$entityName][] = 'get ' . $property->name . '() {';
        $this->getters[$entityName][] = "const data = " . $this->entityManager . ".get(this." . $property->name . "Id,'" . strtolower($c) . "')";
        $this->getters[$entityName][] = 'if(data instanceof ' . $c . '){';
        $this->getters[$entityName][] = "   return data";
        $this->getters[$entityName][] = '}else if(data){';
        $this->getters[$entityName][] = "   return new " . $c . "(data)";
        $this->getters[$entityName][] = '}else{';


        $this->getters[$entityName][] = 'const relation = ' . $this->entityManager . '.getRelation("' . strtolower($c) . '", "' . $inverse . 'Ids",this.id )';

        $this->getters[$entityName][] = 'if(relation instanceof ' . $c . '){';
        $this->getters[$entityName][] = "   return relation";
        $this->getters[$entityName][] = '}else if(relation){';
        $this->getters[$entityName][] = "   return new " . $c . "(relation)";
        $this->getters[$entityName][] = '}';

        $this->getters[$entityName][] = '}';

        // else {
        //     const relation = entityManager.getRelation('subscription', 'billsIds', this.id)
        //     console.log('relation', relation)
        // }
        $this->getters[$entityName][] = '';
        // $this->getters[$entityName][] = 'return;';
        $this->getters[$entityName][] = '}';
        $this->getters[$entityName][] = '';
    }

    private function generateNumber($property, $entityName, ReflectionAttribute $attribute)
    {
        $this->declaration[$entityName][] = 'public ' . $property . ($this->isAttributeNullable($attribute) ? '?' : '!') . ': number;';
        $this->constructor[$entityName][] = '       this.' . $property . '= object.' . $property . ';';
    }

    private function generateOneToOne($property, $entityName, ReflectionAttribute $attribute)
    {
        //        dd($docInfos);
        $target = $attribute->getArguments()['targetEntity'];
        $c = str_replace('App\Entity\\', '', $target);
        if ($c === $entityName) {
            return;
        }
        $import = 'import ' . $c . ' from "./' . $c . '";';
        if (!in_array($import, $this->imports[$entityName], true)) {
            $this->imports[$entityName][] = $import;
        }

        $nullable = $this->isAttributeNullable($attribute);

        $this->declaration[$entityName][] = 'public ' . $property . ($nullable ? '?' : '!') . ': ' . $c . ';';
        $this->constructor[$entityName][] = 'this.' . $property . ' = (object.' . $property . ' instanceof ' . $c . ') ? object.' . $property . ' : object.' . $property . ' ? new ' . $c . '(object.' . $property . ') : object.' . $property . ';';
    }

    private function generateManyToOne($property, $entityName, $target, $relation)
    {
        // dump($property);
//        try {
//            $target = $attribute->getArguments()['targetEntity'];
//        } catch (\Throwable $e) {
//            dump($attribute->getArguments());
//            dd('Classe inconnue : propriété ' . $property->name . ' de la classe ' . $entityName);
//        }
        $c = str_replace('App\Entity\\', '', $target);
        if ($c === $entityName) {
            // dump($c . ' ' . $entityName);
            return;
        }
        $import = 'import ' . $c . ' from "./' . $c . '";';
        if (!in_array($import, $this->imports[$entityName], true)) {
            $this->imports[$entityName][] = $import;
            // $this->generate($target);
        }

        $nullable = $this->isAttributeNullable($relation);

        $this->declaration[$entityName][] = 'public ' . $property->name . 'Id = \'\';';
        // dd($this->declaration[$entityName]);
        $this->constructor[$entityName][] = '   if(object.' . $property->name . 'Id){';
        $this->constructor[$entityName][] = '       this.' . $property->name . 'Id = object.' . $property->name . 'Id';
        $this->constructor[$entityName][] = '   }';

        $this->constructor[$entityName][] = '   if(typeof object.' . $property->name . ' === "string"){';
        $this->constructor[$entityName][] = '       const occ = ' . $this->entityManager . '.get(object.' . $property->name . ', "' . $c . '")';
        $this->constructor[$entityName][] = '       if (occ && typeof occ === "object") {';
        $this->constructor[$entityName][] = '           this.' . $property->name . 'Id = occ.id';
        $this->constructor[$entityName][] = '       }else{';
        $this->constructor[$entityName][] = '           this.' . $property->name . 'Id = object.' . $property->name;
        $this->constructor[$entityName][] = '       }';
        $this->constructor[$entityName][] = '   }else if(object.' . $property->name . ' instanceof ' . $c . '){';
        $this->constructor[$entityName][] = '       this.' . $property->name . 'Id = object.' . $property->name . '.id';

        $this->constructor[$entityName][] = '       }else  if(object.' . $property->name . ' && object.' . $property->name . '.id){';
        $this->constructor[$entityName][] = '       this.' . $property->name . 'Id = object.' . $property->name . '.id';
        $this->constructor[$entityName][] = '       const occ = new ' . $c . '(object.' . $property->name . ')';


        $this->constructor[$entityName][] = '       }else  if(object.' . $property->name . ' && ' . $this->entityManager . '.get(object.' . $property->name . '.id,"' . $c . '") instanceof ' . $c . '){';
        $this->constructor[$entityName][] = '       this.' . $property->name . 'Id = ' . $this->entityManager . '.get(object.' . $property->name . '.id,"' . $c . '").id';
        // $this->constructor[$entityName][] = '       const occ = new ' . $c . '(object.' . $property->name . ')';
        $this->constructor[$entityName][] = '       }';
        // $this->constructor[$entityName][] = '   if(object.' . $property->name . '){';
        // $this->constructor[$entityName][] = '       const ' . $property->name . ' = (object.' . $property->name . ' instanceof ' . $c . ') ? object.'
        //     . $property->name . ' : (object.' . $property->name . ' ? ' . $this->entityManager . '.get([object.' . $property->name . '.id],"' . $c . '") : new ' . $c . '(object.' . $property->name . '));';
        // $this->constructor[$entityName][] = '       if(' . $property->name . ' instanceof ' . $c . ' ){';
        // $this->constructor[$entityName][] = '       this.' . $property->name . 'Id = ' . $property->name . '.id';
        // $this->constructor[$entityName][] = '       }';
        // // $this->constructor[$entityName][] = 'this.' . $property->name . 'Id = (object.' . $property->name . ' instanceof ' . $c . ') ? object.'
        // // . $property->name . ' : object.' . $property->name . ' ? new ' . $c . '(object.' . $property->name . ') : object.' . $property->name . ';';
        // $this->constructor[$entityName][] = '   }';
        $this->generateManyToOneGetter($target, $property, $entityName, $relation);
    }

    private function generateArray($property, $entityName)
    {
        $this->declaration[$entityName][] = 'public ' . $property . '= [];';
        $this->constructor[$entityName][] = '       this.' . $property . '= object.' . $property . ';';
    }

    private function isAttributeNullable(ReflectionAttribute $attribute)
    {
        return array_key_exists('nullable', $attribute->getArguments()) && $attribute->getArguments()['nullable'] === true ? true : false;
    }

    private function generateString($property, $entityName, ReflectionAttribute $attribute)
    {
        $this->declaration[$entityName][] = 'public ' . $property . ($this->isAttributeNullable($attribute) ? '?' : '') . ' = \'\';';
        $this->constructor[$entityName][] = '       this.' . $property . '= object.' . $property . ';';
    }

    private function generateBool($property, $entityName, ReflectionAttribute $attribute)
    {
        $this->declaration[$entityName][] = 'public ' . $property . ($this->isAttributeNullable($attribute) ? '?' : '!') . ': boolean;';
        $this->constructor[$entityName][] = '       this.' . $property . '= object.' . $property . ';';
    }

    private function generateDate($property, $entityName, ReflectionAttribute $attribute)
    {
        $this->declaration[$entityName][] = 'public ' . $property->name . '?: Date;';
        $this->constructor[$entityName][] = '       if(object.' . $property->name . '){';
        $this->constructor[$entityName][] = '           this.' . $property->name . '= new Date(object.' . $property->name . ');';
        $this->constructor[$entityName][] = '       }';
    }
}
