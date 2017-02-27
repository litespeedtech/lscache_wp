<?php




abstract class LiteSpeed_Crawler_Abstract_Sitemap
{
	private $sitemap;

	private $current_position = 0;

	/**
	 * Get the next url using the current position.
	 * @return mixed The url if not done, false on failure, true on completion.
	 */
	abstract public function get_next();

	public function set_current_position($position)
	{
		$this->current_position = $position;
	}

	public function get_current_position()
	{
		return $this->current_position;
	}
}
