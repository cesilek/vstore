<?php
/**
 * This file is part of vStore
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vstore.cz
 * 
 * vStore is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vStore is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vStore bundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vStore\Application\UI\Controls;

use vStore, Nette,
	vBuilder,
	Nette\Application\UI\Form,
	vBuilder\Orm\Repository;
/**
 *
 * @author Jirka
 */
class Search extends Nette\Application\UI\Control {

	
	public function render($what = null) {
		$template = $this->createTemplate();
		$template->setFile(__DIR__.'/templates/'.($what ?: 'full').'.latte');
		echo $template;
	}
	
	public function createComponentSearchForm($name) {
		$form = new Form($this, $name);
		$form->onSuccess[] = callback($this, $name.'Submitted');
		$form->addText('query')
			->setRequired('Please fill in your query')
			->setDefaultValue('Hledat...')
			->addRule(Form::MIN_LENGTH, 'Please make your query at least %d characters long', 3);
		$form->addSubmit('s', 'Search!');
	}
	
	public function searchFormSubmitted(Form $form) {
		dd($form->values);
	}
	
	public function handlePrompt($query) {
		$search = $this->search($query);
		$result = array ();
		foreach ($search as $page) {
			$temp = (object) null;
			$temp->title = $page->title;
			$temp->link = $this->presenter->link('//Redaction:', array (
				'id'=>$page->pageId
			));
			$result[] = $temp;
		}
		$this->presenter->payload->prompt = $result;
		$this->presenter->sendPayload();
	}
	
	protected function search($query) {
		/*
		 *	I'm not quite sure how exactly to improve this very silly algorythm.
		 *  I am not referring to the LIKE operator but to the argument passed
		 *	to the findAll method. The thing is that we might have to search
		 *	through various columns of various entities. I don't really thing
		 *	that our ORM and Branch are ready for this. There might be some
		 *	very significant revisions necessary...
		 */
		$query = '%'.$query.'%';
		return $this->presenter->getContext()->redaction->branch->findAll('vStore\Redaction\Documents\Product')
				->where('([perex] LIKE %s',$query, ') OR ([content] LIKE %s', $query, ') OR ([title] LIKE %s', $query, ')')
				->limit(10)
				->fetchAll();
	}
}