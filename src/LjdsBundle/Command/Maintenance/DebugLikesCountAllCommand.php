<?php

namespace LjdsBundle\Command\Maintenance;

use LjdsBundle\Entity\Gif;
use LjdsBundle\Entity\GifRepository;
use LjdsBundle\Entity\GifState;
use LjdsBundle\Service\FacebookLikesService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prints a JSON structure showing the like count for each gif
 */
class DebugLikesCountAllCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ljds:likes:debug-all')
            ->setDescription('Prints a JSON structure showing the like count for each gif');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var GifRepository $gifRepo */
        $gifRepo = $em->getRepository('LjdsBundle:Gif');
        $gifs = $gifRepo->findByGifState(GifState::PUBLISHED);

        // Get likes!
        /** @var FacebookLikesService $facebookLikesService */
        $facebookLikesService = $this->getContainer()->get('app.facebook_likes');
        $facebookLikesService->fetchLikes($gifs);

        $likes = [];
        /** @var Gif $gif */
        foreach ($gifs as $gif) {
            $likes[] = [
                'permalink' => $gif->getPermalink(),
                'likes' => $gif->getLikes()
            ];
        }

        $output->writeln(json_encode($likes));
    }
}
