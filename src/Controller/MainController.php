<?php

namespace App\Controller;


use App\Entity\Post;
use App\Entity\User;
use App\Form\LoginType;
use App\Form\RegisterType;
use App\Form\CreateNoteType;
use Symfony\Component\Mime\Email;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MainController extends AbstractController
{
    #[Route('/', name: 'root')]
    public function index(SessionInterface $session,Request $request): Response
    {
        $ua = strtolower($request->headers->get('User-Agent'));
        $isMob = is_numeric(strpos($ua, "mobile"));


        $this->session = $session;

        if($session->has('userid')){
            $useragent = $request->headers->get('User-Agent');
            
            return $this->redirect($this->generateUrl('content'));

        }else{
            if($isMob){
                return $this->render('mobile/main/root.html.twig');
            }else{
                return $this->render('desktop/main/root.html.twig');

            }
            

        }

       
        
    }

    #[Route('/login', name:'login')]
    public function login(Request $request, UserRepository $userRepo, SessionInterface $session){
        $ua = strtolower($request->headers->get('User-Agent'));
        $isMob = is_numeric(strpos($ua, "mobile"));
        $this->session = $session;
        if($session->has('userid')){
            return $this->redirect($this->generateUrl('content'));

        }
        $user = new User();

        $form = $this->createForm(LoginType::class,$user);
        
        $incorrectData = 0;
        $validate = 1;
        $deleted = 0;
        

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
    
            if ($form->isSubmitted() && $form->isValid()) {
                $loginData = $form->getData();
                $email = $form['email']->getData();
                $password = $form['password']->getData();
               
                $mail_pwd_check = $this->getDoctrine()
                ->getRepository('App\Entity\User')
                ->findBy(array('email' => $email,'password' => $password));
                
            
                if(count($mail_pwd_check) > 0){
                    if($mail_pwd_check[0]->getDeleted() != "1"){
                        
                        if( $mail_pwd_check[0]->getValidation() == 1){
                            $this->session->set('userid',$mail_pwd_check[0]->getId());
                            return $this->redirect($this->generateUrl('content'));
                        }else{
                            $validate = 0;
                        }

                    }else{
                        
                        $deleted = 1;
                    }
                    
                    
                }
                else{
                    $incorrectData = 1;
                }

            }
        }

        if($isMob){
            return $this->render('mobile/auth/login.html.twig', [
                'form' => $form->createView(),
                'incorrectData' => $incorrectData,
                'path' => '/login',
                'email' => @$email,
                'validate' => $validate,
                'deleted' => $deleted
            ]);

        }else{
            return $this->render('desktop/auth/login.html.twig', [
                'form' => $form->createView(),
                'incorrectData' => $incorrectData,
                'path' => '/login',
                'email' => @$email,
                'validate' => $validate,
                'deleted' => $deleted
            ]);
        }
    }
  
    
    #[Route('/logout', name:'logout')]
    public function logout(SessionInterface $session){
        $session->clear();
        return $this->redirect($this->generateUrl('login'));
        
    }
    #[Route('/content', name:'content')]
    public function content(SessionInterface $session,PostRepository $postRepo,UserRepository $userRepo,Request $request){
        $ua = strtolower($request->headers->get('User-Agent'));
        $isMob = is_numeric(strpos($ua, "mobile"));
        if($session->has('userid')){
            $posts = $postRepo->getPosts($session->get('userid'));
            $username = $userRepo->getUsername($session->get('userid'));
            
           
            if($isMob){
                return $this->render('mobile/main/content.html.twig', [
                    'username' => $username[0]['username'],
                    'posts' => $posts
                    ]);

            }else{
                return $this->render('desktop/main/content.html.twig', [
                    'username' => $username[0]['username'],
                    'posts' => $posts
                    ]);
            }

            

        }else{
            return $this->redirect($this->generateUrl('login'));
        }
        
        
        
    }
    #[Route('/create', name:'create')]
    public function create(SessionInterface $session,Request $request){
        $ua = strtolower($request->headers->get('User-Agent'));
        $isMob = is_numeric(strpos($ua, "mobile"));
        $alert = 0;
        if($session->has('userid')){
            $post = new Post();

            $form = $this->createForm(CreateNoteType::class,$post);


            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) { 
                $entityManager = $this->getDoctrine()->getManager();
                $header = $form['header']->getData();
                $article = $form['article']->getData();

                $post->setUserid($session->get('userid'));
                $post->setHeader($header);
                $post->setArticle($article);
                $post->setColor('bg-success');
                $post->setTextAlign('text-center');

                $entityManager->persist($post);
                $entityManager->flush();
                $alert = 1;

            }

            if($isMob){
                return $this->render('mobile/main/create.html.twig',[
                    'form' => $form->createView(),
                    'alert' => $alert
                ]);

            }else{
                return $this->render('desktop/main/create.html.twig',[
                    'form' => $form->createView(),
                    'alert' => $alert
                ]);
            }


            
        }else{
            return $this->redirect($this->generateUrl('login'));
        }
        
    }

    #[Route('/edit/{postid}', name:'edit')]
    public function edit(SessionInterface $session,Request $request,$postid){
        $ua = strtolower($request->headers->get('User-Agent'));
        $isMob = is_numeric(strpos($ua, "mobile"));
        $alert = 0;
        $this->session = $session;
        if($session->has('userid')){
            
            $em = $this->getDoctrine()->getManager();
            $post = $em->getRepository(Post::class)->find($postid);
            if($post->getUserid() != $session->get('userid')){
                if($isMob){
                    return $this->render('mobile/main/error.html.twig');
                }else{
                    return $this->render('desktop/main/error.html.twig');
                }
                
            }else{
                $postTemp = new Post();

                $form = $this->createForm(CreateNoteType::class,$postTemp);
                $form->handleRequest($request);
                $post2 = array('header' => $post->getHeader(),
                'article' => $post->getArticle());


                if ($form->isSubmitted() && $form->isValid()) { 
                    
                    $header = $form['header']->getData();
                    $article = $form['article']->getData();

                    

                    $post->setHeader($header);
                    $post->setArticle($article);
                    $em->flush();
                    $alert = 1;

                    return $this->redirect($this->generateUrl('content'));
                }
                if($isMob){
                    return $this->render('mobile/main/edit.html.twig', [
                        'form' => $form->createView(),
                        "alert" => $alert,
                        'post' => $post2
                    ]);
                }else{
                    return $this->render('desktop/main/edit.html.twig', [
                        'form' => $form->createView(),
                        "alert" => $alert,
                        'post' => $post2
                    ]);
                }
               
            }

          
        }else{
            if($isMob){
                return $this->render('mobile/auth/login.html.twig');

            }else{
                return $this->render('desktop/auth/login.html.twig');
            }
            
        }
        
        
    }

    #[Route('/register', name:'register')]
    public function register(Request $request,UserRepository $userRepo,MailerInterface $mailer){ 
        $ua = strtolower($request->headers->get('User-Agent'));
        $isMob = is_numeric(strpos($ua, "mobile"));
        $existed = 0;
        $databaseError = 0;
        $checkYourMail = 0;

        $user = new User();

        $form = $this->createForm(RegisterType::class, $user);

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) { 
            
            $entityManager = $this->getDoctrine()->getManager();
            $username = $form['username']->getData();
            $password = $form['password']->getData();
            $email = $form['email']->getData();
            $mailCheck = $this->getDoctrine()
                ->getRepository('App\Entity\User')
                ->findBy(array('email' => $email));


            if(count($mailCheck) > 0){
                
                $existed = 1;

            }
            else{

                $confirm_key = md5($email);
                $role = "USER";
                $validation = 0;

                $user->setUsername($username);
                $user->setPassword($password);
                $user->setEmail($email);
                $user->setValidation($validation);
                $user->setRole($role);
                $user->setConfirmKey($confirm_key);
                $user->setDeleted("0");


                $entityManager->persist($user);
                $entityManager->flush();

                $checkYourMail = 1;

                $objMail= (new Email())
                    ->from('info.notesup@gmail.com')
                    ->to($email)
                    //->cc('cc@example.com')
                    //->bcc('bcc@example.com')
                    //->replyTo('fabien@example.com')
                    //->priority(Email::PRIORITY_HIGH)
                    ->subject('NoteApp Confirmation!')
                    ->text('')
                    ->html('<p>Please confirm your email:  <a href="http://localhost:80/validate/'.$confirm_key.'">Confirm</a></p>');

                $mailer->send($objMail);
                
            }
            
        }
        
        if($isMob){
            return $this->render('mobile/auth/register.html.twig',[
                'form' => $form->createView(),
                'checkYourMail' => $checkYourMail,
                'existed' => $existed,
                'databaseError' => $databaseError,
                'email' => @$email
    
            ]);

        }else{
            return $this->render('desktop/auth/register.html.twig',[
                'form' => $form->createView(),
                'checkYourMail' => $checkYourMail,
                'existed' => $existed,
                'databaseError' => $databaseError,
                'email' => @$email
    
            ]);

        }
        
        
    }
    #[Route('/validate/{confirm_key}', name:'validate')]
    public function validate(Request $request,$confirm_key){
        $ua = strtolower($request->headers->get('User-Agent'));
        $isMob = is_numeric(strpos($ua, "mobile"));
        $entityManager = $this->getDoctrine()->getManager();
        
        $mailCheck = $this->getDoctrine()
                ->getRepository('App\Entity\User')
                ->findBy(array('confirm_key' => $confirm_key));
        if(count($mailCheck) > 0){
            $userid = $mailCheck[0]->getId();
            $user = $entityManager->getRepository('App\Entity\User')->find($userid);
            $user->setValidation('1');
            $entityManager->flush();

            return $this->redirect($this->generateUrl('login'));
        }else{
            if($isMob){
                return $this->render('mobile/auth/validate.html.twig');
            }else{
                return $this->render('desktop/auth/validate.html.twig');
            }
            
        }
        
        
    }
    #[Route('/account', name:'account')]
    public function account(SessionInterface $session, Request $request){
        $ua = strtolower($request->headers->get('User-Agent'));
        $isMob = is_numeric(strpos($ua, "mobile"));
        $this->session = $session;
        if($session->has('userid')){
            
            $em = $this->getDoctrine()->getManager();
            $accountObj = $em->getRepository(User::class)->find($session->get('userid'));
            $account = array("username" => $accountObj->getUsername(),
            "email" => $accountObj->getEmail(),
            "confirmed" => $accountObj->getValidation());
            if($isMob){
                return $this->render('mobile/main/account.html.twig',[
                    'account' => $account
                ]);

            }else{
                return $this->render('desktop/main/account.html.twig',[
                    'account' => $account
                ]);

            }
           
            

        }else{
            return $this->redirect($this->generateUrl('login'));
        }
       
        
        
    }

    #[Route('/text-align/{text_align}/{postid}', name:'text-align')]
    public function textAlign($text_align,$postid){
       
        $entityManager = $this->getDoctrine()->getManager();
        $post = $entityManager->getRepository(Post::class)->find($postid);

        $post->setTextAlign($text_align);

        $entityManager->flush();


        return new JsonResponse("merhaba");


        

    }
    #[Route('/change-color/{postID}/{color}', name:'change-color')]
    public function changeColor($color,$postID){
        
        $entityManager = $this->getDoctrine()->getManager();
        $post = $entityManager->getRepository(Post::class)->find($postID);

        $post->setColor($color);

        $entityManager->flush();



        return new JsonResponse(array($postID,$color));

    }

    #[Route('/delete/{postid}', name:'delete')]
    public function delete($postid, SessionInterface $session){
       
        
        $em = $this->getDoctrine()->getManager();
        $post = $em->getRepository(Post::class)->find($postid);
        if($post->getUserid() != $session->get('userid')){
            if($isMob){
                return $this->render('mobile/main/error.html.twig');
            }else{
                return $this->render('desktop/main/error.html.twig');
            }
           
        }else{

            $em->remove($post);
            $em->flush();
        }
        
        return $this->redirect($this->generateUrl('content'));


        

    }

    #[Route('/change-password/{old}/{new}', name:'changePassword')]
    public function changePassword(SessionInterface $session, $old, $new){
       
        
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->find($session->get('userid'));
        if($old == $user->getPassword()){
            $user->setPassword($new);
            $em->flush();
            return new JsonResponse("OK");
        }else{
            return new JsonResponse("FAIL");

        }

    }

    #[Route('/delete-account', name:'deleteAccount')]
    public function deleteAccount(SessionInterface $session){
        
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->find($session->get('userid'));
        $user->setDeleted('1');
        $em->flush();
        $session->clear();

        return $this->redirect($this->generateUrl('login'));

    }

    #[Route('/account/{error}', name:'account.error')]
    #[Route('/register/{error}', name:'register.error')]
    #[Route('/create/{error}', name:'create.error')]
    #[Route('/content/{error}', name:'content.error')]
    #[Route('/logout/{error}', name:'logout.error')]
    #[Route('/login/{error}', name:'login.error')]
    #[Route('/{error}', name:'root.error')]
    public function errorPage(Request $request){
        $ua = strtolower($request->headers->get('User-Agent'));
        $isMob = is_numeric(strpos($ua, "mobile"));
        if($isMob){
            return $this->render('mobile/main/error.html.twig');
        }else{
            return $this->render('desktop/main/error.html.twig');
        }
        

    }

}
