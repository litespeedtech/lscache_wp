/**
 * CDN module
 * @author Hai Zheng
 */
class CDNMapping extends React.Component {
	constructor( props ) {
		super( props );
		this.state = {
			list: props.list
		};

		this.onChange = this.onChange.bind( this );
		this.delRow = this.delRow.bind( this );
		this.addNew = this.addNew.bind( this );
	}

	onChange( e, index ) {
		const target = e.currentTarget;
		const value = target.dataset.hasOwnProperty('value') ? Boolean(target.dataset.value*1) : target.value;
		const list = this.state.list;
		list[ index ][ target.dataset.type ] = value;

		this.setState( {
		  list: list
		} );
	}

	delRow( index ) {
		const data = this.state.list;
		data.splice( index, 1 );
		this.setState( { list: data } );
	}

	addNew() {
		const list = this.state.list;
		list.push( { url: '' } );
		this.setState( { list: list } );
	}

	render() {
		return (
			<React.Fragment>
			{ this.state.list.map( (item, i) => (
				<CDNMappingBlock item={item} key={i} index={i} onChange={this.onChange} delRow={this.delRow} />
			) ) }

				<p>
					<button type="button" className="button button-link litespeed-form-action litespeed-link-with-icon" onClick={this.addNew}>
						<span className="dashicons dashicons-plus-alt"></span>{litespeed_data[ 'lang' ][ 'add_cdn_mapping_row' ]}
					</button>
				</p>
			</React.Fragment>
		);
	}
}

// { url: '', inc_img: true, inc_css: false, inc_js: false, filetype: [ '.aac', '.eot', ... ] }
class CDNMappingBlock extends React.Component {
	constructor( props ) {
		super( props );

		this.onChange = this.onChange.bind( this );
		this.delRow = this.delRow.bind( this );
	}

	onChange( e ) {
		this.props.onChange( e, this.props.index );
	}

	delRow() {
		this.props.delRow( this.props.index );
	}

	render() {
		const name_prefix = litespeed_data[ 'ids' ][ 'cdn_mapping' ];

		const item = this.props.item;

		const filetype = item.filetype ? Array.isArray(item.filetype) ? item.filetype.join("\n") : item.filetype : '';
		return (
			<div className="litespeed-block">
				<div className='litespeed-cdn-mapping-col1'>
					<label className="litespeed-form-label">{ litespeed_data[ 'lang' ][ 'cdn_mapping_url' ] }</label>
					<input type="text" name={ name_prefix + '[url][]' } className="regular-text litespeed-input-long" value={item.url?item.url:''} data-type="url" onChange={this.onChange} />

					<div className="litespeed-desc">
						<span dangerouslySetInnerHTML={{ __html: litespeed_data[ 'lang' ][ 'cdn_mapping_url_desc' ] }} />
					</div>
				</div>

				<div className="litespeed-col-auto litespeed-cdn-mapping-col2">
					<div className="litespeed-row litespeed-toggle-wrapper">
						<div className="litespeed-cdn-mapping-inc litespeed-form-label litespeed-form-label--toggle">
							{ litespeed_data[ 'lang' ][ 'cdn_mapping_inc_img' ] }
						</div>
						<div className={`litespeed-toggle litespeed-toggle-btn litespeed-toggle-btn-${item.inc_img?'primary':'default litespeed-toggleoff'}`} data-type="inc_img" data-value={item.inc_img?0:1} onClick={this.onChange}>
							<input name={ name_prefix + '[inc_img][]' } type='hidden' value={item.inc_img?1:0} />
							<div className='litespeed-toggle-group'>
								<label className='litespeed-toggle-btn litespeed-toggle-btn-primary litespeed-toggle-on'>{ litespeed_data[ 'lang' ][ 'on' ] }</label>
								<label className='litespeed-toggle-btn litespeed-toggle-btn-default litespeed-toggle-active litespeed-toggle-off'>{ litespeed_data[ 'lang' ][ 'off' ] }</label>
								<span className='litespeed-toggle-handle litespeed-toggle-btn litespeed-toggle-btn-default'></span>
							</div>
						</div>
					</div>
					<div className="litespeed-row litespeed-toggle-wrapper">
						<div className="litespeed-cdn-mapping-inc litespeed-form-label litespeed-form-label--toggle">
							{ litespeed_data[ 'lang' ][ 'cdn_mapping_inc_css' ] }
						</div>
						<div className={`litespeed-toggle litespeed-toggle-btn litespeed-toggle-btn-${item.inc_css?'primary':'default litespeed-toggleoff'}`} data-type="inc_css" data-value={item.inc_css?0:1} onClick={this.onChange}>
							<input name={ name_prefix + '[inc_css][]' } type='hidden' value={item.inc_css?1:0} />
							<div className='litespeed-toggle-group'>
								<label className='litespeed-toggle-btn litespeed-toggle-btn-primary litespeed-toggle-on'>{ litespeed_data[ 'lang' ][ 'on' ] }</label>
								<label className='litespeed-toggle-btn litespeed-toggle-btn-default litespeed-toggle-active litespeed-toggle-off'>{ litespeed_data[ 'lang' ][ 'off' ] }</label>
								<span className='litespeed-toggle-handle litespeed-toggle-btn litespeed-toggle-btn-default'></span>
							</div>
						</div>
					</div>
					<div className="litespeed-row litespeed-toggle-wrapper">
						<div className="litespeed-cdn-mapping-inc litespeed-form-label litespeed-form-label--toggle">
							{ litespeed_data[ 'lang' ][ 'cdn_mapping_inc_js' ] }
						</div>
						<div className={`litespeed-toggle litespeed-toggle-btn litespeed-toggle-btn-${item.inc_js?'primary':'default litespeed-toggleoff'}`} data-type="inc_js" data-value={item.inc_js?0:1} onClick={this.onChange}>
							<input name={ name_prefix + '[inc_js][]' } type='hidden' value={item.inc_js?1:0} />
							<div className='litespeed-toggle-group'>
								<label className='litespeed-toggle-btn litespeed-toggle-btn-primary litespeed-toggle-on'>{ litespeed_data[ 'lang' ][ 'on' ] }</label>
								<label className='litespeed-toggle-btn litespeed-toggle-btn-default litespeed-toggle-active litespeed-toggle-off'>{ litespeed_data[ 'lang' ][ 'off' ] }</label>
								<span className='litespeed-toggle-handle litespeed-toggle-btn litespeed-toggle-btn-default'></span>
							</div>
						</div>
					</div>
				</div>

				<div className="litespeed-col-auto">
					<label className="litespeed-form-label">
						{ litespeed_data[ 'lang' ][ 'cdn_mapping_filetype' ] }
					</label>
					<textarea name={ name_prefix + '[filetype][]' } rows={filetype.split("\n").length+2} cols='18' value={ filetype } data-type="filetype" onChange={this.onChange} />
				</div>

				<div className="litespeed-col-auto">
					<button type="button" className="button button-link litespeed-collection-button litespeed-danger" onClick={this.delRow}>
						<span className="dashicons dashicons-dismiss"></span>
						<span className="screen-reader-text">{ litespeed_data[ 'lang' ][ 'cdn_mapping_remove' ] }</span>
					</button>
				</div>
			</div>
		);
	}
}