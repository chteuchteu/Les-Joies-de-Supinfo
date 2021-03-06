<?php

namespace LjdsBundle\Service;

use DateTime;
use Doctrine\ORM\EntityManager;
use LjdsBundle\Entity\Gif;
use LjdsBundle\Entity\GifRepository;
use LjdsBundle\Entity\GifState;

class GifService
{
    /** @var EntityManager */
    private $em;
    /** @var MailService */
    private $mailService;

    /** @var bool */
    private $facebookAutopost;
    /** @var FacebookService */
    private $facebookService;
    /** @var bool */
    private $twitterAutopost;
    /** @var TwitterService */
    private $twitterService;
    /** @var bool */
    private $pushEnabled;
    /** @var PushNotificationsService */
    private $pushService;

    public function __construct(EntityManager $em, MailService $mailService,
                                $facebookAutopost, FacebookService $facebookService,
                                $twitterAutopost, TwitterService $twitterService,
                                $pushEnabled, PushNotificationsService $pushService)
    {
        $this->em = $em;
        $this->mailService = $mailService;

        $this->facebookAutopost = $facebookAutopost;
        $this->facebookService = $facebookService;
        $this->twitterAutopost = $twitterAutopost;
        $this->twitterService = $twitterService;
        $this->pushEnabled = $pushEnabled;
        $this->pushService = $pushService;
    }

    /**
     * Publishes a gif and posts a link on social networks
     * @param  Gif  $gif
     * @return bool
     */
    public function publish(Gif $gif)
    {
        if (!$gif) {
            return false;
        }

        $gif->setPublishDate(new DateTime());
        $gif->setGifStatus(GifState::PUBLISHED);
        $gif->generateUrlReadyPermalink();

        // Check if permalink is unique
        /** @var GifRepository $gifsRepo */
        $gifsRepo = $this->em->getRepository('LjdsBundle:Gif');
        $permalink = $gif->getPermalink();

        $i = 1;
        while (!empty($gifsRepo->findBy(['permalink' => $gif->getPermalink(), 'gifStatus' => GifState::PUBLISHED]))) {
            // Generate a new permalink
            $gif->setPermalink($permalink.$i);
            ++$i;
        }

        $this->em->flush();

        if ($this->facebookAutopost) {
            $this->facebookService->postGif($gif);
        }

        if ($this->twitterAutopost) {
            $this->twitterService->postGif($gif);
        }

        if ($this->pushEnabled) {
            $this->pushService->notify($gif);
        }

        if ($gif->getEmail() != null) {
            $this->mailService->sendGifPublishedMail($gif);
        }

        return true;
    }
}
