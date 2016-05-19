<?php

namespace fitch\fields;

class OneRelation extends \fitch\fields\Relation {
	public function isMany() {
		return false;
	}
}

?>