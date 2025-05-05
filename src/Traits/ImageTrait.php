<?php

namespace App\Traits;

use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\HasLifecycleCallbacks()
 */
trait  ImageTrait
{
    protected $file;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['minimal', 'photo', 'prospect', 'manager'])]
    protected $photo;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected $alt;

    protected $ext;

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(UploadedFile $file): void
    {
//        $name = $this->uploadToFile($file);
//        $this->photo = $name;
        $this->file = $file;
//        dump($file->guessExtension());

    }

//    abstract public function getFolder(): string;

    abstract public function getPreferredSize(): array;

    public function getDefaultPreferredSize(): array
    {
        return ['width' => 500, 'height' => 500, 'method' => 'fit'];
    }

    public function resize($path)
    {
        $imageManager = new ImageManager();
        $img = $imageManager->make($path);
        $ratio = $this->getPreferredSize();
        $width = (int)$ratio['width'];
        $height = (int)$ratio['height'];
        $method = $ratio['method'];
//        dd($ratio);
        switch ($method) {
            case 'stretch':
                {
                    $img->resize($ratio['width'], $ratio['height']);
                    break;
                }
            case 'fit':
                {
                    if ($width) {
                        $img->widen($ratio['width']);
                    }
                    if ($height) {
                        $img->heighten($ratio['height']);
                    }
                    break;
                }
            case 'crop':
                {
                    $img->fit($ratio['width'], $ratio['height']);
                    break;
                }
        }
        $img->save($path);
    }

    abstract public function getFileName(): string;

    /**
     * @param UploadedFile $file
     * @param string $name
     * @return string
     */
    #[ORM\PreFlush]
    public function uploadToFile($args)
    {

        /** @var UploadedFile $file */
        $file = $this->file;
        if (!$file) {
            return null;
        }
        $base = __DIR__ . '/../../public/images/';
        $imageFolder = $base . 'uploads/';
//        if (method_exists($this, 'getFolder')) {
//            $folder = $this->getFolder();
//            if (!empty($folder)) {
//                $imageFolder = $base . $folder . '/';
//            }
//        }
        if (!is_dir($imageFolder)) {
            if (!mkdir($imageFolder) && !is_dir($imageFolder)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $imageFolder));
            }
        }
        if (file_exists($imageFolder . $this->getPhoto())) {
            @unlink($imageFolder . $this->getPhoto());
        }
        $name = $this->slugify($this->getFileName());
        if (empty($name)) {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $name = $this->slugify($originalFilename);
        }
        $fileName = $name . '-' . uniqid('', false) . '.' . $file->getClientOriginalExtension();
        $this->photo = $fileName;
        $this->alt = $name;
//        dd($fileName);

        try {
            $file->move($imageFolder, $fileName);
            $this->resize($imageFolder . $fileName);
        } catch (FileException $e) {
        }
        return $fileName;
    }

    /**
     * @param $text
     * @param string $replaceBy
     * @param bool $lowerCase
     * @return false|string|string[]|null
     *
     */
    public function slugify($text, $replaceBy = '_', $lowerCase = false)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', $replaceBy, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', $replaceBy, $text);
        $text = preg_replace('/\./', $replaceBy, $text);

        // lowercase
        if ($lowerCase) {
            $text = strtolower($text);
        }

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * @return mixed
     */
    public function getAlt()
    {
        return $this->alt;
    }

    /**
     * @param mixed $alt
     */
    public function setAlt($alt): void
    {
        $this->alt = $alt;
    }

    /**
     * @return mixed
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @param mixed $photo
     */
    public function setPhoto($photo): void
    {
        $this->photo = $photo;
    }
}
