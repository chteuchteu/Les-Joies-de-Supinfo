<?php

namespace LjdsBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use LjdsBundle\Entity\Gif;
use LjdsBundle\Entity\GifRepository;
use LjdsBundle\Entity\GifState;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api")
 */
class ApiController extends Controller
{
    const LIST_DEFAULT = 20;
    const LIST_MAX = 75;

    private function isMainRoute(Request $request, $mainRoute)
    {
        return $request->get('_route') === $mainRoute;
    }

    /**
     * @Route("/gif/random", name="api_gif_random")
     * @Route("/random", name="api_gif_random_old")
     * @Method({"GET"})
     */
    public function apiRandomGifAction(Request $request)
    {
        // Redirect to main route
        if (!$this->isMainRoute($request, 'api_gif_random')) {
            return $this->redirectToRoute('api_gif_random');
        }

        $em = $this->getDoctrine()->getManager();
        /** @var GifRepository $gifsRepo */
        $gifsRepo = $em->getRepository('LjdsBundle:Gif');

        /** @var Gif $gif */
        $gif = $gifsRepo->getRandomGif();

        return new JsonResponse($gif->toJson($this->get('router')));
    }

    /**
     * @Route("/gif/latest", name="api_gif_latest")
     * @Route("/last", name="api_gif_latest_old")
     * @Method({"GET"})
     */
    public function apiLatestGifAction(Request $request)
    {
        // Redirect to main route
        if (!$this->isMainRoute($request, 'api_gif_latest')) {
            return $this->redirectToRoute('api_gif_latest');
        }

        $em = $this->getDoctrine()->getManager();
        /** @var GifRepository $gifsRepo */
        $gifsRepo = $em->getRepository('LjdsBundle:Gif');

        /** @var Gif $gif */
        $gif = $gifsRepo->getLastPublishedGif();

        return new JsonResponse($gif->toJson($this->get('router')));
    }

    /**
     * Returns X latest published gifs
     * @Route("/gif/list")
     * @Method({"GET"})
     */
    public function apiLatestGifsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $maxResults = $request->query->get('count', self::LIST_DEFAULT);

        if (!filter_var($maxResults, FILTER_VALIDATE_INT) || $maxResults > self::LIST_MAX) {
            $maxResults = self::LIST_DEFAULT;
        }

        // Create query
        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $query = $qb->select('g')
            ->from('LjdsBundle\Entity\Gif', 'g')
            ->where('g.gifStatus = '.GifState::PUBLISHED)
            ->orderBy('g.publishDate', 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery();

        $query->execute();

        $gifs = array_map(function (Gif $gif) {
            return $gif->toJson($this->get('router'));
        }, $query->getResult());

        return new JsonResponse($gifs);
    }
}
