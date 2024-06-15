<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Form\RegistroFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistroController extends AbstractController
{
    private $verifyEmailHelper;
    private $mailer;
    private $entityManager;

    public function __construct(VerifyEmailHelperInterface $verifyEmailHelper, MailerInterface $mailer, EntityManagerInterface $entityManager)
    {
        $this->verifyEmailHelper = $verifyEmailHelper;
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
    }

    #[Route('/registro', name: 'app_registro')]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new Usuario();
        $form = $this->createForm(RegistroFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validar si el correo electrónico ya está en uso
            $email = $form->get('email')->getData();
            $existingUser = $entityManager->getRepository(Usuario::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('warning', 'El correo electrónico ya está registrado. Por favor, elige otro.');
                return $this->render('registro/index.html.twig', [
                    'registroForm' => $form->createView(),
                ]);
            }

            // Encode the plain password
            $plaintextPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plaintextPassword
            );
            $user->setPassword($hashedPassword);

            // Asignar roles basado en el dominio del correo electrónico
            $roles = ['ROLE_USER']; // El rol ROLE_USER siempre se asigna
            if (str_contains($email, '@electro-gamer.com')) {
                $roles[] = 'ROLE_ADMIN'; // Asignar también ROLE_ADMIN si el email contiene @electro-gamer.com
            }
            $user->setRoles($roles);

            // Obtener y establecer otros campos del formulario
            $nombre = $form->get('nombre')->getData();
            if ($nombre) {
                $user->setNombre($nombre);
            }
            $apellidos = $form->get('apellidos')->getData();
            if ($apellidos) {
                $user->setApellidos($apellidos);
            }

            // Manejar la subida de la foto del usuario
            $foto = $form->get('photo')->getData();
            if ($foto) {
                $originalFilename = pathinfo($foto->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$foto->guessExtension();

                // Definir la ruta completa a la carpeta de subidas
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/usuarios';

                // Mover el archivo a la carpeta de subidas /public/uploads/usuarios
                try {
                    $foto->move(
                        $uploadsDirectory,
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Manejar la excepción si ocurre durante el movimiento del archivo
                    $this->addFlash('warning', 'No se pudo subir la foto del usuario. Por favor, inténtalo de nuevo.');
                    return $this->render('registro/index.html.twig', [
                        'registroForm' => $form->createView(),
                    ]);
                }
                $user->setPhoto($newFilename);
            }

            // Persistir el usuario en la base de datos
            $entityManager->persist($user);
            $entityManager->flush();

            // Generar la firma para verificar el email
            $signatureComponents = $this->verifyEmailHelper->generateSignature(
                'app_verify_email',
                $user->getId(),
                $user->getEmail(),
                ['id' => $user->getId()]
            );

            // Enviar el email de verificación
            $email = (new Email())
                ->from('no-reply@electro-gamer.com')
                ->to($user->getEmail())
                ->subject('Bienvenido a Electro Gamer')
                ->html('<p>Para verificar tu correo electrónico, por favor haz clic en el siguiente enlace: <a href="' . $signatureComponents->getSignedUrl() . '">Verificar Email</a></p>');

            $this->mailer->send($email);

            // Redirigir al login
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registro/index.html.twig', [
            'registroForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request): Response
    {
        $userId = $request->get('id');

        // Recuperar al usuario según el ID
        $user = $this->entityManager->getRepository(Usuario::class)->find($userId);

        if (null === $user) {
            throw $this->createNotFoundException();
        }

        // Validar el enlace de verificación
        try {
            $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
            // Si la verificación es exitosa, marcar el usuario como verificado
            $user->setVerified(1);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_email_verificado');
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('verify_email_error', $e->getReason());

            return $this->redirectToRoute('app_register');
        }
    }

    #[Route('/email_verificado', name: 'app_email_verificado')]
    public function emailVerificado(Request $request): Response
    {
        return $this->render('registro/email_verificado.html.twig');
    }
}
