<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Publication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Compilation;
use AppBundle\Entity\Contribution;
use AppBundle\Form\CompilationType;
use AppBundle\Form\ContributionType;

/**
 * Compilation controller.
 *
 * @Route("/compilation")
 */
class CompilationController extends Controller
{
    /**
     * Lists all Compilation entities.
     *
     * @Route("/", name="compilation_index", methods={"GET"})
     *
     * @Template()
	 * @param Request $request
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $pageSize = $this->getParameter('page_size');

        if($request->query->has('alpha')) {
            $repo = $em->getRepository(Publication::class);
            $page = $repo->letterPage($request->query->get('alpha'), Publication::COMPILATION, $pageSize);
            return $this->redirectToRoute('compilation_index', array('page' => $page));
        }

        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Compilation::class, 'e')->orderBy('e.sortableTitle', 'ASC');
        $query = $qb->getQuery();
        $paginator = $this->get('knp_paginator');
        $compilations = $paginator->paginate($query, $request->query->getint('page', 1), $pageSize);

        $letterIndex = array();
        foreach($compilations as $compilation) {
            $title = $compilation->getSortableTitle();
            if( ! $title) {
                continue;
            }
            $letterIndex[mb_convert_case($title[0], MB_CASE_UPPER)] = 1;
        }

        return array(
            'compilations' => $compilations,
            'activeLetters' => array_keys($letterIndex),
        );
    }

    /**
     * Creates a new Compilation entity.
     *
     * @Route("/new", name="compilation_new", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_CONTENT_EDITOR')")
     * @Template()
	 * @param Request $request
     */
    public function newAction(Request $request)
    {
        $compilation = new Compilation();
        $form = $this->createForm(CompilationType::class, $compilation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach($compilation->getContributions() as $contribution) {
                $contribution->setPublication($compilation);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($compilation);
            $em->flush();

            $this->addFlash('success', 'The new collection was created.');
            return $this->redirectToRoute('compilation_show', array('id' => $compilation->getId()));
        }

        return array(
            'compilation' => $compilation,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Compilation entity.
     *
     * @Route("/{id}", name="compilation_show", methods={"GET"})
     *
     * @Template()
	 * @param Compilation $compilation
     */
    public function showAction(Compilation $compilation)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Compilation::class);

        return array(
            'compilation' => $compilation,
            'next' => $repo->next($compilation),
            'previous' => $repo->previous($compilation),
        );
    }

    /**
     * Displays a form to edit an existing Compilation entity.
     *
     * @Route("/{id}/edit", name="compilation_edit", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_CONTENT_EDITOR')")
     * @Template()
	 * @param Request $request
	 * @param Compilation $compilation
     */
    public function editAction(Request $request, Compilation $compilation)
    {
        $editForm = $this->createForm(CompilationType::class, $compilation);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            foreach($compilation->getContributions() as $contribution) {
                $contribution->setPublication($compilation);
            }
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The collection has been updated.');
            return $this->redirectToRoute('compilation_show', array('id' => $compilation->getId()));
        }

        return array(
            'compilation' => $compilation,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a Compilation entity.
     *
     * @Route("/{id}/delete", name="compilation_delete", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_CONTENT_ADMIN')")
	 * @param Request $request
	 * @param Compilation $compilation
     */
    public function deleteAction(Request $request, Compilation $compilation)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($compilation);
        $em->flush();
        $this->addFlash('success', 'The compilation was deleted.');

        return $this->redirectToRoute('compilation_index');
    }

    /**
     * Creates a new Compilation contribution entity.
     *
     * @Route("/{id}/contributions/new", name="compilation_new_contribution")
     *
     * @Security("is_granted('ROLE_CONTENT_EDITOR')")
     * @Template()
     * @param Request $request
     * @param Compilation $compilation
     */
    public function newContribution(Request $request, Compilation $compilation) {
        $contribution = new Contribution();

        $form = $this->createForm(ContributionType::class, $contribution);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contribution->setPublication($compilation);
            $em = $this->getDoctrine()->getManager();
            $em->persist($contribution);
            $em->flush();

            $this->addFlash('success', 'The new contribution was created.');
            return $this->redirectToRoute('compilation_show_contributions', array('id' => $compilation->getId()));
        }

        return array(
            'compilation' => $compilation,
            'form' => $form->createView(),
        );
    }
    
    /**
     * Show compilation contributions list with edit/delete action items
     * 
     * @Route("/{id}/contributions", name="compilation_show_contributions")
     *
     * @Security("is_granted('ROLE_CONTENT_EDITOR')")
     * @Template()
     * @param Compilation $compilation
     */
    public function showContributions(Compilation $compilation) {
        return array(
            'compilation' => $compilation,
        );
    }
    
    /**
     * Displays a form to edit an existing compilation Contribution entity.
     *
     * @Route("/contributions/{id}/edit", name="compilation_edit_contributions")
     *
     * @Security("is_granted('ROLE_CONTENT_EDITOR')")
     * @Template()
     * @param Request $request
     * @param Contribution $contribution
     */
    public function editContribution(Request $request, Contribution $contribution) {
        $editForm = $this->createForm(ContributionType::class, $contribution);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', 'The contribution has been updated.');
            return $this->redirectToRoute('compilation_show_contributions', array('id' => $contribution->getPublicationId()));
        }

        return array(
            'contribution' => $contribution,
            'edit_form' => $editForm->CreateView(),
        );
    }

    /**
     * Deletes a compilation Contribution entity.
     *
     * @Route("/contributions/{id}/delete", name="compilation_delete_contributions")
     *
     * @param Request $request
     * @Security("is_granted('ROLE_CONTENT_ADMIN')")
     * @param Contribution $contribution
     */
    public function deleteContribution(Request $request, Contribution $contribution) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($contribution);
        $em->flush();
        $this->addFlash('success', 'The contribution was deleted.');

        return $this->redirectToRoute('compilation_show_contributions', array('id' => $contribution->getPublicationId()));
    }

}
