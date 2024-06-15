<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Producto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $precio = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $descuento = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $categoria = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $marca = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true, options: ["default" => "default_Imagen.png"])]
    #[Assert\Regex(pattern:"/^[\w]+\.(jpg|jpeg|png|webp)$/i", message:"Imagen no válida")]
    private ?string $imagen = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $stock = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $estado = null;

    #[ORM\OneToMany(targetEntity: Pregunta::class, mappedBy: 'producto', orphanRemoval: true)]
    private Collection $preguntas;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): self
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    public function getPrecio(): ?float
    {
        return $this->precio;
    }

    public function setPrecio(float $precio): self
    {
        $this->precio = $precio;
        return $this;
    }

    public function getDescuento(): ?float
    {
        return $this->descuento;
    }

    public function setDescuento(float $descuento): self
    {
        $this->descuento = $descuento;
        return $this;
    }

    public function getCategoria(): ?string
    {
        return $this->categoria;
    }

    public function setCategoria(string $categoria): self
    {
        $this->categoria = $categoria;
        return $this;
    }

    public function getMarca(): ?string
    {
        return $this->marca;
    }

    public function setMarca(string $marca): self
    {
        $this->marca = $marca;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getImagen(): ?string
    {
        return $this->imagen;
    }

    public function setImagen(string $imagen): self
    {
        $this->imagen = $imagen;
        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;
        return  $this;
    }

    public function getEstado(): ?bool
    {
        return $this->estado;
    }

    public function setEstado(bool $estado): self
    {
        $this->estado = $estado;
        return $this;
    }



    /**
     * @return Collection<int, Pregunta>
     */
    public function getPreguntas(): Collection
    {
        return $this->preguntas;
    }

    public function addPregunta(Pregunta $pregunta): self
    {
        if (!$this->preguntas->contains($pregunta)) {
            $this->preguntas->add($pregunta);
            $pregunta->setProducto($this);
        }
        return $this;
    }

    public function removePregunta(Pregunta $pregunta): self
    {
        if ($this->preguntas->contains($pregunta)) {
            $this->preguntas->removeElement($pregunta);
            if ($pregunta->getProducto() === $this) {
                $pregunta->setProducto(null);
            }
        }
        return $this;
    }

}