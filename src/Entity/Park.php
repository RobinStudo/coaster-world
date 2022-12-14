<?php

namespace App\Entity;

use App\Repository\ParkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: ParkRepository::class)]
class Park
{
    const TYPES = [
        0 => "Parc d'attractions",
        1 => "Parc aquatique",
        2 => "Parc de loisirs"
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: "Veuillez saisir un nom")]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: "Le nom doit comporter au moins {{ limit }} caractères",
        maxMessage: "Le nom doit comporter au maximum {{ limit }} caractères",
    )]
    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[Assert\Expression(
        "this.getImage() or this.getImageFile()",
        message: 'Veuillez choisir une image',
    )]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png'],
        maxSizeMessage: 'L\'image ne doit pas éxcéder 2Mo',
        mimeTypesMessage: 'L\'image doit être au format JPEG ou PNG',
    )]
    private ?UploadedFile $imageFile = null;

    #[Assert\NotBlank(message: "Veuillez choisir un type")]
    #[ORM\Column]
    private ?int $type = null;

    #[Assert\Url(message: "Vous devez saisir une URL valide")]
    #[ORM\Column(length: 250, nullable: true)]
    private ?string $website = null;

    #[Assert\Valid]
    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Address $address = null;

    #[ORM\OneToMany(mappedBy: 'park', targetEntity: Coaster::class, orphanRemoval: true)]
    private Collection $coasters;

    public function __construct()
    {
        $this->coasters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getImageFile(): ?UploadedFile
    {
        return $this->imageFile;
    }

    public function setImageFile(?UploadedFile $imageFile): self
    {
        $this->imageFile = $imageFile;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getCoasters(): Collection
    {
        return $this->coasters;
    }

    public function addCoaster(Coaster $coaster): self
    {
        if (!$this->coasters->contains($coaster)) {
            $this->coasters->add($coaster);
            $coaster->setPark($this);
        }

        return $this;
    }

    public function removeCoaster(Coaster $coaster): self
    {
        if ($this->coasters->removeElement($coaster)) {
            if ($coaster->getPark() === $this) {
                $coaster->setPark(null);
            }
        }

        return $this;
    }

    /* --- Custom methods --- */

    public function displayType(): string
    {
        return self::TYPES[$this->getType()];
    }
}
