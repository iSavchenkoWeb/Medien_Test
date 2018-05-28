<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\User as User;
use AppBundle\Entity\Post as Post;

class DefaultControllerTest extends WebTestCase
{
    private $objectManager;

    public function testIndex()
    {
        $TEST_USERNAME = 'unittestuser';
        $TEST_PASS = 'testp';
        $TEST_EMAIL = 'test@gmail.com';

        /*test user creation*/
            $client = static::createClient();
            $this->objectManager = $client->getContainer()->get('doctrine.orm.entity_manager');
            $crawler = $client->request('GET', '/');

            $this->assertEquals(302, $client->getResponse()->getStatusCode());
            $crawler = $client->request('GET', '/login');
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
            $this->assertContains('Username', $crawler->filter('.container')->text());
            $this->assertContains('Password', $crawler->filter('.container')->text());

            


            $crawler = $client->request('GET', '/register/');
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
            $form = $crawler->selectButton('Register')->form(array(
                    'fos_user_registration_form[email]' => $TEST_EMAIL,
                    'fos_user_registration_form[username]' => $TEST_USERNAME,
                    'fos_user_registration_form[plainPassword][first]' => $TEST_PASS,
                    'fos_user_registration_form[plainPassword][second]' => $TEST_PASS
                )
            );

            $client->enableProfiler();
            $crawler = $client->submit($form);
            $profile = $client->getProfile();
            $collector = $profile->getCollector('swiftmailer');
            $found = false;
            foreach ($collector->getMessages() as $message) {
                // Checking the recipient email and the X-Swift-To
                // header to handle the RedirectingPlugin.
                // If the recipient is not the expected one, check
                // the next mail.
                $correctRecipient = array_key_exists(
                    $TEST_EMAIL, $message->getTo()
                );
                $headers = $message->getHeaders();
                $correctXToHeader = false;
                if ($headers->has('X-Swift-To')) {
                    $correctXToHeader = array_key_exists($TEST_EMAIL,
                        $headers->get('X-Swift-To')->getFieldBodyModel()
                    );
                }
                if (!$correctRecipient && !$correctXToHeader) {
                    continue;
                }
                if (strpos($message->getBody(), 'To finish activating your account - please visit') !== false) {
                  $found = true;
                  break;
                }
            }
            $this->assertTrue($found, 'Email was not sent to ' . $TEST_EMAIL);

            $crawler = $client->followRedirect();
            $this->assertContains('An email has been sent to', $crawler->filter('.container')->text());
            
            $usersRep = $this->objectManager->getRepository('AppBundle:User');
            $user = $usersRep->findBy(array('username' => $TEST_USERNAME));
            $this->assertInternalType('array',$user);
            $user = $user[0];
            $this->assertInstanceOf(User::class, $user);
            $code = $user->getConfirmationToken();

            $crawler = $client->request('GET', '/register/confirm/'.$code);
            $this->assertEquals(302, $client->getResponse()->getStatusCode());
            $client->followRedirect();
            $this->assertEquals(200, $client->getResponse()->getStatusCode());

        /*test post creation*/
            $crawler = $client->request('GET', '/');
            $this->assertEquals(302, $client->getResponse()->getStatusCode());
            $client->followRedirect();
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
            $crawler = $client->request('GET', '/posts');
            $this->assertEquals(200, $client->getResponse()->getStatusCode());

            $rnd_content = 'TestPostContent: '.rand(3,99999);
            $form = $crawler->selectButton('Post')->form(array(
                    'appbundle_post[content]' => $rnd_content
                )
            );
            $client->request($form->getMethod(), '/api/posts', $form->getPhpValues());

            $postsRep = $this->objectManager->getRepository('AppBundle:Post');
            $post = $postsRep->findBy(array('author' => $user));
            $this->assertInternalType('array',$post);
            $post = $post[0];
            $this->assertInstanceOf(Post::class, $post);
            $this->assertRegExp('#'.$rnd_content.'#', $post->getContent());
            
            $crawler = $client->request('GET', '/');
            $this->assertEquals(302, $client->getResponse()->getStatusCode());
            $crawler = $client->followRedirect();
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
            $this->assertContains($rnd_content, $crawler->filter('.container')->text());

        /*clear tests data*/
            $this->objectManager->remove($post);
            $this->objectManager->flush();

            $this->objectManager->remove($user);
            $this->objectManager->flush();
    }
}
