<?php

// src/Entity/Interaccion.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\InteraccionRepository;

#[ORM\Entity(repositoryClass: InteraccionRepository::class)]
#[ORM\Table(name: 'interacciones')]
class Interaccion
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: "usuario_id", nullable: false)]
    private ?Usuario $usuario = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Producto::class)]
    #[ORM\JoinColumn(name: "producto_id", nullable: false)]
    private ?Producto $producto = null;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $tipo_interaccion;

    // Getters and Setters
    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): self
    {
        $this->usuario = $usuario;
        return $this;
    }

    public function getProducto(): ?Producto
    {
        return $this->producto;
    }

    public function setProducto(?Producto $producto): self
    {
        $this->producto = $producto;
        return $this;
    }

    public function getTipoInteraccion(): int
    {
        return $this->tipo_interaccion;
    }

    public function setTipoInteraccion(int $tipo_interaccion): self
    {
        $this->tipo_interaccion = $tipo_interaccion;
        return $this;
    }
}
