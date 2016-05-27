<?php
namespace Apple_Exporter\Components;

use \Apple_Exporter\Exporter as Exporter;

/**
 * A paragraph component.
 *
 * @since 0.2.0
 */
class Body extends Component {

	/**
	 * Override. This component doesn't need a layout update if marked as the
	 * target of an anchor.
	 *
	 * @var boolean
	 * @access public
	 */
	public $needs_layout_if_anchored = false;

	/**
	 * Quotes can be anchor targets.
	 *
	 * @var boolean
	 * @access protected
	 */
	protected $can_be_anchor_target = true;

	/**
	 * Look for node matches for this component.
	 *
	 * @param DomNode $node
	 * @return mixed
	 * @static
	 * @access public
	 */
	public static function node_matches( $node ) {
		// We are only interested in p, ul and ol
		if ( ! in_array( $node->nodeName, array( 'p', 'ul', 'ol' ) ) ) {
			return null;
		}

		// If the node is p, ul or ol AND it's empty, just ignore.
		if ( empty( $node->nodeValue ) ) {
			return null;
		}

		// There are several components which cannot be translated to markdown,
		// namely images, videos, audios and EWV. If these components are inside a
		// paragraph, split the paragraph.
		if ( 'p' == $node->nodeName ) {
			$html = $node->ownerDocument->saveXML( $node );
			return self::split_non_markdownable( $html );
		}

		return $node;
	}

	/**
	 * Split the non markdownable content for processing.
	 *
	 * @param string $html
	 * @return array
	 * @static
	 * @access private
	 */
	private static function split_non_markdownable( $html ) {
		if ( empty( $html ) ) {
			return array();
		}

		preg_match( '#<(img|video|audio|iframe).*?(?:>(.*?)</\1>|/?>)#si', $html, $matches );

		if ( ! $matches ) {
			return array( array( 'name' => 'p', 'value' => $html ) );
		}

		list( $whole, $tag_name ) = $matches;
		list( $left, $right )     = explode( $whole, $html, 3 );

		$para = array( 'name' => 'p', 'value' => self::clean_html( $left . '</p>' ) );
		// If the paragraph is empty, just return the right-hand-side
		if ( '<p></p>' == $para['value'] ) {
			return array_merge(
				array( array( 'name' => $tag_name, 'value' => $whole ) ),
				self::split_non_markdownable( self::clean_html( '<p>' . $right ) )
			);
		}

		return array_merge(
		 	array(
				$para,
				array( 'name'  => $tag_name, 'value' => $whole ),
		 	),
			self::split_non_markdownable( self::clean_html( '<p>' . $right ) )
		);
	}

	/**
	 * Build the component.
	 *
	 * @param string $text
	 * @access protected
	 */
	protected function build( $text ) {
		$this->json = array(
			'role'   => 'body',
			'text'   => $this->markdown->parse( $text ),
			'format' => 'markdown',
		);

		if ( 'yes' == $this->get_setting( 'initial_dropcap' ) ) {
			// Toggle setting. This should only happen in the initial paragraph.
			$this->set_setting( 'initial_dropcap', 'no' );
			$this->set_initial_dropcap_style();
		} else {
			$this->set_default_style();
		}

		$this->set_default_layout();
	}

	/**
	 * Get the start column for the body based on the layout.
	 *
	 * @access private
	 */
	private function get_col_start() {
		// Find out where the body must start according to the body orientation.
		// Orientation defaults to left, thus, col_start is 0.
		$col_start = 0;
		switch ( $this->get_setting( 'body_orientation' ) ) {
		case 'right':
			$col_start = $this->get_setting( 'layout_columns' ) - $this->get_setting( 'body_column_span' );
			break;
		case 'center':
			$col_start = floor( ( $this->get_setting( 'layout_columns' ) - $this->get_setting( 'body_column_span' ) ) / 2 );
			break;
		}
	}

	/**
	 * Set the default layout for the component.
	 *
	 * @access public
	 */
	public function set_default_layout() {
		$this->json[ 'layout' ] = 'body-layout';
		$this->register_layout( 'body-layout', array(
			'columnStart' => $this->get_col_start(),
			'columnSpan'  => $this->get_setting( 'body_column_span' ),
			'margin'      => array(
				'top' => 12,
				'bottom' => 12
			),
		) );
	}

	/**
	 * Set the layout for the last body component.
	 *
	 * @access public
	 */
	public function set_last_layout() {
		$this->json[ 'layout' ] = 'body-layout-last';
		$this->register_layout( 'body-layout-last', array(
			'columnStart' => $this->get_col_start(),
			'columnSpan'  => $this->get_setting( 'body_column_span' ),
			'margin'      => array(
				'top' => 12,
				'bottom' => 30
			),
		) );
	}

	/**
	 * Get the default style for the component.
	 *
	 * @return array
	 * @access private
	 */
	private function get_default_style() {
		return array(
			'textAlignment' 			=> 'left',
			'fontName'      			=> $this->get_setting( 'body_font' ),
			'fontSize'      			=> intval( $this->get_setting( 'body_size' ) ),
			'lineHeight'    			=> intval( $this->get_setting( 'body_line_height' ) ),
			'textColor'     			=> $this->get_setting( 'body_color' ),
			'linkStyle'     			=> array(
				'textColor' => $this->get_setting( 'body_link_color' )
			),
			'paragraphSpacingBefore' 	=> 18,
			'paragraphSpacingAfter'		=> 18,
		);
	}

	/**
	 * Set the default style for the component.
	 *
	 * @access public
	 */
	public function set_default_style() {
		$this->json[ 'textStyle' ] = 'default-body';
		$this->register_style( 'default-body', $this->get_default_style() );
	}

	/**
	 * Set the initial dropcap style for the component.
	 *
	 * @access private
	 */
	private function set_initial_dropcap_style() {
		$this->json[ 'textStyle' ] = 'dropcapBodyStyle';
		$this->register_style( 'dropcapBodyStyle', array_merge(
			$this->get_default_style(),
		 	array(
				'dropCapStyle' => array (
					'numberOfLines' 		=> 4,
					'numberOfCharacters' 	=> 1,
					'padding' 				=> 5,
					'fontName' 				=> $this->get_setting( 'dropcap_font' ),
					'textColor'				=> $this->get_setting( 'dropcap_color' ),
				),
			)
	 	) );
	}
}

