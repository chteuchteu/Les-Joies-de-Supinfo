<?php

namespace LjdsBundle\Controller;

use LjdsBundle\Entity\GifRepository;
use LjdsBundle\Helper\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubmittersController extends Controller
{
    /**
     * @Route("/submitters", name="topSubmitters")
     */
    public function submittersTopAction()
    {
        throw new NotFoundHttpException();
        return $this->render('LjdsBundle:Submitters:top.html.twig', [
            'submitters' => $this->get('app.facebook_likes')->getTopSubmitters()
        ]);
    }

    /**
     * @Route("/submitter/{submitter}", name="submitter")
     * @Route("/submitter/{submitter}/page/{page}", name="submitter_page")
     */
    public function submitterGifsAction($submitter, $page = 1, $_route)
    {
        throw new NotFoundHttpException();
        $em = $this->getDoctrine()->getManager();
        /** @var GifRepository $gifRepo */
        $gifRepo = $em->getRepository('LjdsBundle:Gif');

        if (strlen($submitter) == 0) {
            throw new NotFoundHttpException();
        }
        $qb = $gifRepo->findBySubmitter_queryBuilder($submitter);
        $query = $qb->getQuery();
        $query->execute();
        $gifs = $query->getResult();

        // Don't serve pages for unknown persons
        if (count($gifs) == 0) {
            throw new NotFoundHttpException();
        }
        // Fetch likes counts
        $likesCount = $this->get('app.facebook_likes')->getLikesCountForSubmitter($submitter);

        // Pagination
        $page = (int) $page;
        $gifsPerPage = (int) ($this->getParameter('gifs_per_page'));

        // Redirect /submitter/{submitter}/page to /submitter/{submitter}
        if ($page == 1 && $_route == 'submitter_page') {
            return $this->redirectToRoute('submitter', ['submitter' => $submitter]);
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $page,
            $gifsPerPage
        );
        $pagination->setUsedRoute('submitter_page');

        // Redirect when trying to hit wrong page
        $totalCount = Util::getPaginationTotalCount($pagination);
        $pagesCount = ceil($totalCount / $gifsPerPage);

        if ($pagesCount == 0) {
            throw new NotFoundHttpException();
        } elseif ($page < 1) {
            return $this->redirectToRoute('submitter_page', ['page' => 1, 'submitter' => $submitter]);
        } elseif ($page > $pagesCount) {
            return $this->redirectToRoute('submitter_page', ['page' => $pagesCount, 'submitter' => $submitter]);
        }

        return $this->render('LjdsBundle:Submitters:submitter.html.twig', [
            'submitter' => $submitter,
            'gifs' => $pagination,
            'page' => $page,
            'likes_count' => $likesCount
        ]);
    }
}
