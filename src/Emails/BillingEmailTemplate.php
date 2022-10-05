<?php

namespace Stackonet\WP\Framework\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Class BillingEmailTemplate
 *
 * @package Stackonet\WP\Framework\Emails
 */
class BillingEmailTemplate extends EmailTemplateBase {

	/**
	 * Start of a new row
	 *
	 * @param string $style Style attribute.
	 *
	 * @return string
	 */
	public function row_start( string $style = '' ): string {
		$html  = '<div class="email-row" style="' . $this->get_unique_styles( $style ) . '">';
		$html .= '<table style="width:100%;max-width:600px;margin:0;padding:0;" width="600" align="center" cellpadding="0" cellspacing="0"><tr>';

		return $html;
	}

	/**
	 * End of a row
	 *
	 * @return string
	 */
	public function row_end(): string {
		return '</tr></table></div>' . PHP_EOL;
	}

	/**
	 * Start of a column
	 *
	 * @param string $style Style attribute.
	 *
	 * @return string
	 */
	public function column_start( string $style = '' ): string {
		$style = $this->get_style( 'font-family' ) . 'vertical-align:top;' . $style;

		return '<td style="' . $this->get_unique_styles( $style ) . '">';
	}

	/**
	 * End of column
	 *
	 * @return string
	 */
	public function column_end(): string {
		return '</td>' . PHP_EOL;
	}

	/**
	 * Start of a table
	 *
	 * @param string $style Style attribute.
	 *
	 * @return string
	 */
	public function table_start( string $style = '' ): string {
		$table_style = 'color: #636363;border: none;vertical-align: middle;width: 100%;margin-bottom:15px;';

		return '<div style="' . $style . '"><table cellspacing="0" cellpadding="0" border="1" style="' . $table_style . '">';
	}

	/**
	 * End of a table
	 *
	 * @return string
	 */
	public function table_end(): string {
		return '</table></div>' . PHP_EOL;
	}

	/**
	 * Build table header
	 *
	 * @param array  $columns Columns.
	 * @param string $styles Styles.
	 *
	 * @return string
	 */
	public function table_head( array $columns, string $styles = '' ): string {
		$th_style  = 'background-color:#f5f5f5;color: #636363; font-size:14px;border: none; vertical-align: middle; padding: 8px; text-align: left;';
		$th_style .= $styles;
		$html      = '<thead>';
		$html     .= '<tr>';
		foreach ( $columns as $column ) {
			$column_label = isset( $column['label'] ) ? esc_html( $column['label'] ) : '';
			if ( isset( $column['numeric'] ) && true === $column['numeric'] ) {
				$th_style .= 'text-align:right;';
			}
			$html .= '<th style="' . $this->get_unique_styles( $th_style ) . '">' . esc_html( $column_label ) . '</th>' . PHP_EOL;
		}
		$html .= '</tr>' . PHP_EOL;
		$html .= '</thead>' . PHP_EOL;

		return $html;
	}

	/**
	 * Build table body
	 *
	 * @param array  $columns Columns.
	 * @param array  $data Data.
	 * @param string $styles Styles.
	 *
	 * @return string
	 */
	public function table_body( array $columns, array $data, string $styles = '' ): string {
		$last_index  = count( $data ) - 1;
		$cell_style  = 'font-size:13px; color: #636363; border: none; vertical-align: middle; padding: 8px;';
		$cell_style .= $styles;

		$html = '<tbody>';
		foreach ( $data as $index => $value ) {
			if ( $index < $last_index ) {
				$cell_style .= 'border-bottom:1px dashed #e5e5e5;';
			} else {
				$cell_style .= 'border-bottom:none;';
			}
			$html .= '<tr>';
			foreach ( $columns as $column ) {
				if ( isset( $column['numeric'] ) && true === $column['numeric'] ) {
					$cell_style .= 'text-align:right;';
				} else {
					$cell_style .= 'text-align:left;';
				}
				$key    = isset( $column['key'] ) ? esc_html( $column['key'] ) : '';
				$_value = $value[ $key ] ?? '';
				$html  .= '<td style="' . $this->get_unique_styles( $cell_style ) . '">' . $_value . '</td>' . PHP_EOL;
			}
			$html .= '</tr>' . PHP_EOL;
		}
		$html .= '</tbody>' . PHP_EOL;

		return $html;
	}

	/**
	 * Add table foot
	 *
	 * @param array $rows Rows.
	 *
	 * @return string
	 */
	public function table_foot( array $rows ): string {
		$html = '<tfoot>';
		foreach ( $rows as $row ) {
			$html .= $this->table_foot_row( $row );
		}
		$html .= '</tfoot>' . PHP_EOL;

		return $html;
	}

	/**
	 * Add table row data
	 *
	 * @param array $row Row.
	 *
	 * @return string
	 */
	public function table_foot_row( array $row ): string {
		$_style = 'font-size:13px; color: #636363; border: none; border-top: 1px solid #e5e5e5; vertical-align: middle; padding: 8px;';
		$html   = '<tr>';
		foreach ( $row as $cell ) {
			$label   = $cell['label'] ?? '';
			$colspan = isset( $cell['colspan'] ) ? 'colspan="' . intval( $cell['colspan'] ) . '"' : '';

			$cell_style = $_style;

			$style = $cell['style'] ?? '';
			if ( ! empty( $style ) ) {
				$cell_style .= $style;
			}

			if ( isset( $cell['numeric'] ) && true === $cell['numeric'] ) {
				$cell_style .= 'text-align:right;';
			} else {
				$cell_style .= 'text-align:left;';
			}

			$html .= '<th style="' . $this->get_unique_styles( $cell_style ) . '" ' . $colspan . '>' . PHP_EOL;
			$html .= $label;
			$html .= '</th>' . PHP_EOL;
		}
		$html .= '</tr>' . PHP_EOL;

		return $html;
	}
}
