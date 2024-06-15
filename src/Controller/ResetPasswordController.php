<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\Persistence\Proxy;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Display & process form to request a password reset.
     */
    #[Route('/contrasena_olvidada', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        $form = $this->createFormBuilder()
            ->add('email', null, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Introduce tu correo electrónico'],
                'label' => 'Correo electrónico',
                'label_attr' => ['class' => 'form-label']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $mailer,
                $translator
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not.
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and processes the reset URL that the user clicked in their email.
     */
    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, ?string $token = null): Response
    {
        // Si se proporciona un token en la URL, almacenarlo en la sesión y redirigir al mismo método sin token en la URL
        if ($token) {
            $this->storeTokenInSession($token);
            return $this->redirectToRoute('app_reset_password');
        }

        // Obtener el token de la sesión
        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw $this->createNotFoundException('No se ha encontrado un token de restablecimiento de contraseña en la URL o en la sesión.');
        }

        try {
            // Validar el token y obtener el usuario correspondiente
            /** @var Usuario $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);

            // Añade una línea de depuración aquí
            if ($user instanceof \Doctrine\Persistence\Proxy) {
                $this->entityManager->initializeObject($user);
            }

            // Verifica que el objeto es de la clase esperada
            if (!$user instanceof Usuario) {
                throw new \LogicException('El objeto no es una instancia de Usuario.');
            }

        } catch (ResetPasswordExceptionInterface $e) {
            // Agregar un mensaje de error si la validación del token falla
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // Crear y manejar el formulario de cambio de contraseña
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        // Depuración del estado del formulario
        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                foreach ($form->getErrors(true) as $error) {
                    // Muestra todos los errores del formulario
                    error_log($error->getMessage());
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Eliminar la solicitud de restablecimiento de contraseña ahora que se ha utilizado
            $this->resetPasswordHelper->removeResetRequest($token);

            // Codificar la nueva contraseña
            $encodedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            // Establecer la nueva contraseña para el usuario y guardar los cambios en la base de datos
            $user->setPassword($encodedPassword);
            $this->entityManager->flush();

            // Limpiar la sesión después de cambiar la contraseña
            $this->cleanSessionAfterReset();

            // Redirigir al usuario a la página de inicio de sesión
            return $this->redirectToRoute('app_login');
        }

        // Renderizar la plantilla de restablecimiento de contraseña
        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(), // Asegúrate de pasar el createView() al renderizado del formulario
        ]);
    }


    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer, TranslatorInterface $translator): RedirectResponse
    {
        $user = $this->entityManager->getRepository(Usuario::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->redirectToRoute('app_check_email');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('sergioAO@electro-gamer.com', 'Electro Gamer Tech'))
            ->to($user->getEmail())
            ->subject('Tu solicitud de cambio de contraseña')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        $mailer->send($email);

        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }
}
