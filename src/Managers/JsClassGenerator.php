<?php

namespace App\Managers;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use ReflectionAttribute;
use Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;

class JsClassGenerator
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var PropertyInfoExtractor */
    private $propertyInfo;

    private $root = 'App\Entity\\';
    private $entity = '';
    private $classes = [];
    private $avoidClasses = ['File', 'Folder', 'FileSource'];
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

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $reflectionExtractor = new ReflectionExtractor();
        $doctrineExtractor = new DoctrineExtractor($em);
        $this->propertyInfo = new PropertyInfoExtractor(
        // List extractors
            [
                $reflectionExtractor,
                $doctrineExtractor
            ],
            // Type extractors
            [
                $doctrineExtractor,
                $reflectionExtractor
            ]
        );
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
            if (in_array($entityName, $this->avoidClasses, false)) {
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
                    $this->generateCollection($property->name, $entityName, str_replace('App\Entity\\', '', $type), $mappedBy, $inversedBy);
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
                    $this->generateOneToOne($property, $entityName, $relation);
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
//                    dump($entityName . '->' . $property->name . ' = ' . $targetEntity);
                    $this->generateManyToOne($property, $entityName, $targetEntity, $relation);
                    $this->generate(str_replace('App\Entity\\', '', $targetEntity));
                }
                if ($relation->getName() === OneToMany::class) {
                    $type = $relation->getArguments()['targetEntity'];
                    $mappedBy = $relation->getArguments()['mappedBy'];
                    $this->generateCollection($property->name, $entityName, str_replace('App\Entity\\', '', $type), $mappedBy);
                    $list = true;
                }
                foreach ($attributes as $attribute) {
                    $args = $attribute->getArguments();

                    if (array_key_exists('type', $args)) {
                    } else if ($property->getType()) {
                        $args['type'] = $property->getType()->getName();
                    } else {
                        $args['type'] = 'string';
                    }
                    if ($attribute->getName() === Column::class) {

                        $type = $args['type'];
                        $nullable = array_key_exists('nullable', $args) && $args['nullable'] === true ? true : false;

                        $this->form[$entityName][] = $property->name . ':[null' . ($nullable ? '' : ',Validators.required') . '],';

                        switch ($type) {
                            case 'integer':
                            case 'float':
                            case 'smallint':
                            case 'bigint':
                            case 'decimal':
                            case 'int':
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
                                $this->generateDate($property->name, $entityName, $attribute);
                                break;
                            }
                            case 'boolean':
                            case 'bool':
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

    public function finalize($entity)
    {

        $content = $this->imports[$entity] ?? [];
        array_unshift($content, '/* eslint-disabled */');
        $content[] = 'import ' . $entity . 'Extend from "./extends/' . $entity . 'Extend";';
        $content[] = '';
        $content[] = 'export default class ' . $entity . ' extends ' . $entity . 'Extend {';
        $content[] = '';
        //    dd($this->declaration[$entity]);
        $content = array_merge($content, $this->declaration[$entity]);
        $content[] = '';
        $content[] = '  constructor (object?: any) {';
        $content[] = '      super(object)';
        $content[] = '      if(object){';
        $content = array_merge($content, $this->constructor[$entity]);
        $content[] = '      }';
        $content[] = '      this.postConstruct()';
        $content[] = '  }';
        $content[] = '';
        $content[] = '}';
        //        dd($this->form);
        $dir = __DIR__ . './../../jsClasses/vueJS/';

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

        $dir = __DIR__ . './../../jsClasses/vueJS/extends/';

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
        $class = str_replace('App\Entity\\', '', $class);
        $import = 'import ' . $class . ' from "./' . $class . '";';
        if (!in_array($import, $this->imports[$entityName], true) && $entityName !== $class) {
            $this->imports[$entityName][] = $import;
        }

        $declaration = 'public ' . $property . ': Array<' . $class . '> = [];';
        if (!in_array($entityName, $this->declaration, true)) {
            $this->declaration[$entityName][] = 'public ' . $property . ': Array<' . $class . '> = [];';
        }
        if (!array_key_exists($entityName, $this->properties)) {
            $this->properties[$entityName] = [];
        }
        if (!in_array($property, $this->properties[$entityName], true)) {
            $this->constructor[$entityName][] = '       if(object.' . $property . '){';
            $this->constructor[$entityName][] = '           object.' . $property . '.forEach((occ: any)=>{';
//            if ($mappedBy) {
//                $this->constructor[$entityName][] = '               occ.' . $mappedBy . ' = this;';
//            }
            $this->constructor[$entityName][] = '               const ' . strtolower($class) . '= occ instanceof ' . $class . '? occ :  new ' . $class . '(occ);';
            //            $this->constructor[$entityName][] = '               ' . strtolower($class) . '.' . $mappedBy . ' = this ;';
            $this->constructor[$entityName][] = '               this.' . $property . '.push(' . strtolower($class) . ');';
            $this->constructor[$entityName][] = '           });';
            $this->constructor[$entityName][] = '       }';
            $this->properties[$entityName][] = $property;
        }
        $this->generate($class);
    }

    private function generateNumber($property, $entityName, ReflectionAttribute $attribute)
    {
        $this->declaration[$entityName][] = 'public ' . $property . ($this->isAttributeNullable($attribute) ? '?' : '!') . ': number;';
        $this->constructor[$entityName][] = '       this.' . $property . '= object.' . $property . ';';
    }

    private function generateOneToOne(\ReflectionProperty $property, $entityName, ReflectionAttribute $attribute)
    {

        if ($property->getType()) {
            $target = $property->getType()->getName();
        } elseif (isset($attribute->getArguments()['targetEntity'])) {
            $target = $attribute->getArguments()['targetEntity'];
        }
        $c = str_replace('App\Entity\\', '', $target);
        if ($c === $entityName) {
            return;
        }
        $import = 'import ' . $c . ' from "./' . $c . '";';
        if (!in_array($import, $this->imports[$entityName], true)) {
            $this->imports[$entityName][] = $import;
        }

        $nullable = $this->isAttributeNullable($attribute);

        $this->declaration[$entityName][] = 'public ' . $property->name . ($nullable ? '?' : '!') . ': ' . $c . ';';
        $this->constructor[$entityName][] = 'this.' . $property->name . ' = (object.' . $property->name . ' instanceof ' . $c . ') ? object.' .
            $property->name . ' : object.' . $property->name . ' ? new ' . $c . '(object.' . $property->name . ') : object.' . $property->name . ';';
    }

    private function generateManyToOne($property, $entityName, $targetEntity, $relation)
    {

//        $target = $attribute->getArguments()['targetEntity'];
        $c = str_replace('App\Entity\\', '', $targetEntity);
//        if ($c === $entityName) {
//            dd($c,$entityName);
//            return;
//        }

        $import = 'import ' . $c . ' from "./' . $c . '";';
        if (!in_array($import, $this->imports[$entityName], true)) {
            if ($c !== $entityName) {
                $this->imports[$entityName][] = $import;
            }
            $this->generate($targetEntity);
        }

//        dd($property);
        $nullable = $this->isAttributeNullable($relation);

        $this->declaration[$entityName][] = 'public ' . $property->name . ($nullable ? '?' : '!') . ': ' . $c . ';';
        //        $this->constructor[$entityName][] = 'if(object.' . $property . '){';
        $this->constructor[$entityName][] = 'this.' . $property->name . ' = (object.' . $property->name . ' instanceof ' . $c . ') ? object.' .
            $property->name . ' : object.' . $property->name . ' ? new ' . $c . '(object.' . $property->name . ') : object.' . $property->name . ';';
        //        $this->constructor[$entityName][] = '}';
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
//        dump($entityName . '->' . $property);
        $this->declaration[$entityName][] = 'public ' . $property . ($this->isAttributeNullable($attribute) ? '?' : '!') . ': boolean;';
        $this->constructor[$entityName][] = '       this.' . $property . '= object.' . $property . ';';
    }

    private function generateDate($property, $entityName, ReflectionAttribute $attribute)
    {
        $this->declaration[$entityName][] = 'public ' . $property . '?: Date;';
        $this->constructor[$entityName][] = '       if(object.' . $property . '){';
        $this->constructor[$entityName][] = '           this.' . $property . '= new Date(object.' . $property . ');';
        $this->constructor[$entityName][] = '       }';
    }
}
