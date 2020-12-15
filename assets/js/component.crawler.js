/**
 * Crawler simulation module
 * @author Hai Zheng
 */
class CrawlerSimulate extends React.Component {
	constructor( props ) {
		super( props );
		this.state = {
			list: props.list
		};

		this.handleInputChange = this.handleInputChange.bind( this );
		this.delRow = this.delRow.bind( this );
		this.addNew = this.addNew.bind( this );
	}

	handleInputChange( e, index ) {
		const target = e.target;
		const value = target.type === 'checkbox' ? target.checked : target.value;
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
		list.push( { name: '', vals: '' } );
		this.setState( { list: list } );
	}

	render() {
		return (
			<React.Fragment>
			{ this.state.list.map( (item, i) => (
				<SimulationBlock item={item} key={i} index={i} handleInputChange={this.handleInputChange} delRow={this.delRow} />
			) ) }

				<p>
					<button type="button" className="button button-link litespeed-form-action litespeed-link-with-icon" onClick={this.addNew}>
						<span className="dashicons dashicons-plus-alt"></span>{litespeed_data[ 'lang' ][ 'add_cookie_simulation_row' ]}
					</button>
				</p>
			</React.Fragment>
		);
	}
}

// { name: '', vals: '' }
class SimulationBlock extends React.Component {
	constructor( props ) {
		super( props );

		this.handleInputChange = this.handleInputChange.bind( this );
		this.delRow = this.delRow.bind( this );
	}

	handleInputChange( e ) {
		this.props.handleInputChange( e, this.props.index );
	}

	delRow() {
		this.props.delRow( this.props.index );
	}

	render() {
		const item = this.props.item;
		return (
			<div className="litespeed-block">
				<div className="litespeed-col-auto">
					<label className="litespeed-form-label">{ litespeed_data[ 'lang' ][ 'cookie_name' ] }</label>
					<input type="text" name={ litespeed_data[ 'ids' ][ 'crawler_cookies' ] + '[name][]' } className="regular-text" value={item.name} data-type="name" onChange={this.handleInputChange} />
				</div>
				<div className="litespeed-col-auto">
					<label className="litespeed-form-label">{ litespeed_data[ 'lang' ][ 'cookie_values' ] }</label>
					<textarea rows="5" cols="40" name={ litespeed_data[ 'ids' ][ 'crawler_cookies' ] + '[vals][]' } placeholder={ litespeed_data[ 'lang' ][ 'one_per_line' ] } value={ Array.isArray(item.vals) ? item.vals.join("\n") : item.vals } data-type="vals" onChange={this.handleInputChange} />
				</div>
				<div className="litespeed-col-auto">
					<button type="button" className="button button-link litespeed-collection-button litespeed-danger" onClick={this.delRow}>
						<span className="dashicons dashicons-dismiss"></span>
						<span className="screen-reader-text">{ litespeed_data[ 'lang' ][ 'remove_cookie_simulation' ] }</span>
					</button>
				</div>
			</div>

		);
	}
}