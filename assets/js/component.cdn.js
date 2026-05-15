/**
 * CDN module
 * @author Hai Zheng
 */

// Extensions auto-injected into the filetype textarea when their toggle is switched ON.
// Switching OFF intentionally does NOT remove — the user must remove manually.
const LITESPEED_CDN_TOGGLE_AUTOADD = {
	inc_css: ['.css', '.less'],
	inc_js: ['.js'],
};

// Reverse map used to flag extensions in the filetype list whose corresponding toggle is OFF.
const LITESPEED_CDN_EXT_TO_TOGGLE = {
	'.css': 'inc_css', '.less': 'inc_css',
	'.js': 'inc_js',
	'.gif': 'inc_img', '.jpeg': 'inc_img', '.jpg': 'inc_img',
	'.png': 'inc_img', '.svg': 'inc_img', '.webp': 'inc_img',
	'.pdf': 'inc_docs',
	'.eot': 'inc_fonts', '.otf': 'inc_fonts', '.ttf': 'inc_fonts',
	'.woff': 'inc_fonts', '.woff2': 'inc_fonts',
	'.aac': 'inc_media', '.mp3': 'inc_media',
	'.mp4': 'inc_media', '.ogg': 'inc_media',
};

class CDNMapping extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			list: props.list,
		};

		this.onChange = this.onChange.bind(this);
		this.delRow = this.delRow.bind(this);
		this.addNew = this.addNew.bind(this);
	}

	onChange(e, index) {
		const target = e.currentTarget;
		const value = target.dataset.hasOwnProperty('value') ? Boolean(target.dataset.value * 1) : target.value;
		const list = this.state.list;
		const type = target.dataset.type;
		list[index][type] = value;

		// When inc_css / inc_js is switched ON, auto-append its file extensions to the filetype textarea
		// so the toggle works for non-enqueued references too. Toggling OFF never removes — that stays manual.
		if (value === true && LITESPEED_CDN_TOGGLE_AUTOADD[type]) {
			const raw = list[index].filetype;
			const current = (raw ? (Array.isArray(raw) ? raw.slice() : String(raw).split('\n')) : [])
				.map((s) => s.trim())
				.filter(Boolean);
			const lower = new Set(current.map((s) => s.toLowerCase()));
			const toAdd = LITESPEED_CDN_TOGGLE_AUTOADD[type].filter((ext) => !lower.has(ext.toLowerCase()));
			if (toAdd.length > 0) {
				list[index].filetype = current.concat(toAdd).join('\n');
			}
		}

		this.setState({
			list: list,
		});
	}

	delRow(index) {
		const data = this.state.list;
		data.splice(index, 1);
		this.setState({ list: data });
	}

	addNew() {
		const list = this.state.list;
		list.push({ url: '' });
		this.setState({ list: list });
	}

	render() {
		return (
			<React.Fragment>
				{this.state.list.map((item, i) => (
					<CDNMappingBlock item={item} key={i} index={i} onChange={this.onChange} delRow={this.delRow} />
				))}

				<p>
					<button type="button" className="button button-link litespeed-form-action litespeed-link-with-icon" onClick={this.addNew}>
						<span className="dashicons dashicons-plus-alt"></span>
						{litespeed_data['lang']['add_cdn_mapping_row']}
					</button>
				</p>
			</React.Fragment>
		);
	}
}

// { url: '', inc_img: true, inc_css: false, inc_js: false, inc_docs: true, inc_fonts: true, inc_media: true, filetype: [ '.aac', '.eot', ... ] }
class CDNMappingBlock extends React.Component {
	constructor(props) {
		super(props);

		this.onChange = this.onChange.bind(this);
		this.delRow = this.delRow.bind(this);
		this.addMissingDefaults = this.addMissingDefaults.bind(this);
	}

	onChange(e) {
		this.props.onChange(e, this.props.index);
	}

	delRow() {
		this.props.delRow(this.props.index);
	}

	// Split a filetype value (array or newline string) into a clean array of extensions.
	splitFiletype(value) {
		if (!value) return [];
		const arr = Array.isArray(value) ? value : String(value).split('\n');
		return arr.map((s) => s.trim()).filter(Boolean);
	}

	// Defaults from the localize bundle that aren't in the user's current saved list (case-insensitive).
	getMissingDefaults() {
		const defaults = (litespeed_data && litespeed_data['cdn_mapping_filetype_default']) || [];
		const current = this.splitFiletype(this.props.item.filetype);
		const currentSet = new Set(current.map((s) => s.toLowerCase()));
		return defaults.filter((ext) => !currentSet.has(String(ext).trim().toLowerCase()));
	}

	// Extensions that ARE in the current list but whose corresponding toggle is OFF — the generic
	// file-type rewriter would still send them through the CDN, contradicting the toggle.
	getInconsistent() {
		const item = this.props.item;
		const current = this.splitFiletype(item.filetype);
		const seen = new Set();
		const result = [];
		for (const ext of current) {
			const key = ext.toLowerCase();
			if (seen.has(key)) continue;
			seen.add(key);
			const toggle = LITESPEED_CDN_EXT_TO_TOGGLE[key];
			if (toggle && !item[toggle]) {
				result.push(ext);
			}
		}
		return result;
	}

	addMissingDefaults() {
		const missing = this.getMissingDefaults();
		if (missing.length === 0) return;

		const current = this.splitFiletype(this.props.item.filetype);
		const newValue = current.concat(missing).join('\n');

		// Reuse the existing change pipeline by synthesising a textarea-style event.
		this.props.onChange(
			{ currentTarget: { value: newValue, dataset: { type: 'filetype' } } },
			this.props.index
		);
	}

	render() {
		const name_prefix = litespeed_data['ids']['cdn_mapping'];

		const item = this.props.item;

		const filetype = item.filetype ? (Array.isArray(item.filetype) ? item.filetype.join('\n') : item.filetype) : '';
		const defaults = (litespeed_data && litespeed_data['cdn_mapping_filetype_default']) || [];
		const missing = this.getMissingDefaults();
		const inconsistent = this.getInconsistent();

		// Size the readonly defaults textarea to mirror PHP's `recommended()` helper.
		const defaultsRows = Math.min(Math.max(defaults.length + 1, 5), 40);
		let defaultsCols = 30;
		for (const v of defaults) {
			if (String(v).length > defaultsCols) defaultsCols = String(v).length;
		}
		defaultsCols = Math.min(defaultsCols, 150);
		return (
			<div className="litespeed-block">
				<div className="litespeed-cdn-mapping-col1">
					<label className="litespeed-form-label">{litespeed_data['lang']['cdn_mapping_url']}</label>
					<input
						type="text"
						name={name_prefix + '[url][]'}
						className="regular-text litespeed-input-long"
						value={item.url ? item.url : ''}
						data-type="url"
						onChange={this.onChange}
					/>

					<div className="litespeed-desc">
						<span dangerouslySetInnerHTML={{ __html: litespeed_data['lang']['cdn_mapping_url_desc'] }} />
					</div>
				</div>

				<div className="litespeed-col-auto litespeed-cdn-mapping-col2">
					<div className="litespeed-row litespeed-toggle-wrapper">
						<div className="litespeed-cdn-mapping-inc litespeed-form-label litespeed-form-label--toggle">{litespeed_data['lang']['cdn_mapping_inc_img']}</div>
						<div
							className={`litespeed-toggle litespeed-toggle-btn litespeed-toggle-btn-${item.inc_img ? 'primary' : 'default litespeed-toggleoff'}`}
							data-type="inc_img"
							data-value={item.inc_img ? 0 : 1}
							onClick={this.onChange}
						>
							<input name={name_prefix + '[inc_img][]'} type="hidden" value={item.inc_img ? 1 : 0} />
							<div className="litespeed-toggle-group">
								<label className="litespeed-toggle-btn litespeed-toggle-btn-primary litespeed-toggle-on">{litespeed_data['lang']['on']}</label>
								<label className="litespeed-toggle-btn litespeed-toggle-btn-default litespeed-toggle-active litespeed-toggle-off">
									{litespeed_data['lang']['off']}
								</label>
								<span className="litespeed-toggle-handle litespeed-toggle-btn litespeed-toggle-btn-default"></span>
							</div>
						</div>
					</div>
					<div className="litespeed-row litespeed-toggle-wrapper">
						<div className="litespeed-cdn-mapping-inc litespeed-form-label litespeed-form-label--toggle">{litespeed_data['lang']['cdn_mapping_inc_css']}</div>
						<div
							className={`litespeed-toggle litespeed-toggle-btn litespeed-toggle-btn-${item.inc_css ? 'primary' : 'default litespeed-toggleoff'}`}
							data-type="inc_css"
							data-value={item.inc_css ? 0 : 1}
							onClick={this.onChange}
						>
							<input name={name_prefix + '[inc_css][]'} type="hidden" value={item.inc_css ? 1 : 0} />
							<div className="litespeed-toggle-group">
								<label className="litespeed-toggle-btn litespeed-toggle-btn-primary litespeed-toggle-on">{litespeed_data['lang']['on']}</label>
								<label className="litespeed-toggle-btn litespeed-toggle-btn-default litespeed-toggle-active litespeed-toggle-off">
									{litespeed_data['lang']['off']}
								</label>
								<span className="litespeed-toggle-handle litespeed-toggle-btn litespeed-toggle-btn-default"></span>
							</div>
						</div>
					</div>
					<div className="litespeed-row litespeed-toggle-wrapper">
						<div className="litespeed-cdn-mapping-inc litespeed-form-label litespeed-form-label--toggle">{litespeed_data['lang']['cdn_mapping_inc_js']}</div>
						<div
							className={`litespeed-toggle litespeed-toggle-btn litespeed-toggle-btn-${item.inc_js ? 'primary' : 'default litespeed-toggleoff'}`}
							data-type="inc_js"
							data-value={item.inc_js ? 0 : 1}
							onClick={this.onChange}
						>
							<input name={name_prefix + '[inc_js][]'} type="hidden" value={item.inc_js ? 1 : 0} />
							<div className="litespeed-toggle-group">
								<label className="litespeed-toggle-btn litespeed-toggle-btn-primary litespeed-toggle-on">{litespeed_data['lang']['on']}</label>
								<label className="litespeed-toggle-btn litespeed-toggle-btn-default litespeed-toggle-active litespeed-toggle-off">
									{litespeed_data['lang']['off']}
								</label>
								<span className="litespeed-toggle-handle litespeed-toggle-btn litespeed-toggle-btn-default"></span>
							</div>
						</div>
					</div>
					<div className="litespeed-row litespeed-toggle-wrapper">
						<div className="litespeed-cdn-mapping-inc litespeed-form-label litespeed-form-label--toggle">{litespeed_data['lang']['cdn_mapping_inc_docs']}</div>
						<div
							className={`litespeed-toggle litespeed-toggle-btn litespeed-toggle-btn-${item.inc_docs ? 'primary' : 'default litespeed-toggleoff'}`}
							data-type="inc_docs"
							data-value={item.inc_docs ? 0 : 1}
							onClick={this.onChange}
						>
							<input name={name_prefix + '[inc_docs][]'} type="hidden" value={item.inc_docs ? 1 : 0} />
							<div className="litespeed-toggle-group">
								<label className="litespeed-toggle-btn litespeed-toggle-btn-primary litespeed-toggle-on">{litespeed_data['lang']['on']}</label>
								<label className="litespeed-toggle-btn litespeed-toggle-btn-default litespeed-toggle-active litespeed-toggle-off">
									{litespeed_data['lang']['off']}
								</label>
								<span className="litespeed-toggle-handle litespeed-toggle-btn litespeed-toggle-btn-default"></span>
							</div>
						</div>
					</div>
					<div className="litespeed-row litespeed-toggle-wrapper">
						<div className="litespeed-cdn-mapping-inc litespeed-form-label litespeed-form-label--toggle">{litespeed_data['lang']['cdn_mapping_inc_fonts']}</div>
						<div
							className={`litespeed-toggle litespeed-toggle-btn litespeed-toggle-btn-${item.inc_fonts ? 'primary' : 'default litespeed-toggleoff'}`}
							data-type="inc_fonts"
							data-value={item.inc_fonts ? 0 : 1}
							onClick={this.onChange}
						>
							<input name={name_prefix + '[inc_fonts][]'} type="hidden" value={item.inc_fonts ? 1 : 0} />
							<div className="litespeed-toggle-group">
								<label className="litespeed-toggle-btn litespeed-toggle-btn-primary litespeed-toggle-on">{litespeed_data['lang']['on']}</label>
								<label className="litespeed-toggle-btn litespeed-toggle-btn-default litespeed-toggle-active litespeed-toggle-off">
									{litespeed_data['lang']['off']}
								</label>
								<span className="litespeed-toggle-handle litespeed-toggle-btn litespeed-toggle-btn-default"></span>
							</div>
						</div>
					</div>
					<div className="litespeed-row litespeed-toggle-wrapper">
						<div className="litespeed-cdn-mapping-inc litespeed-form-label litespeed-form-label--toggle">{litespeed_data['lang']['cdn_mapping_inc_media']}</div>
						<div
							className={`litespeed-toggle litespeed-toggle-btn litespeed-toggle-btn-${item.inc_media ? 'primary' : 'default litespeed-toggleoff'}`}
							data-type="inc_media"
							data-value={item.inc_media ? 0 : 1}
							onClick={this.onChange}
						>
							<input name={name_prefix + '[inc_media][]'} type="hidden" value={item.inc_media ? 1 : 0} />
							<div className="litespeed-toggle-group">
								<label className="litespeed-toggle-btn litespeed-toggle-btn-primary litespeed-toggle-on">{litespeed_data['lang']['on']}</label>
								<label className="litespeed-toggle-btn litespeed-toggle-btn-default litespeed-toggle-active litespeed-toggle-off">
									{litespeed_data['lang']['off']}
								</label>
								<span className="litespeed-toggle-handle litespeed-toggle-btn litespeed-toggle-btn-default"></span>
							</div>
						</div>
					</div>
				</div>

				<div className="litespeed-col-auto">
					<label className="litespeed-form-label">{litespeed_data['lang']['cdn_mapping_filetype']}</label>
					<textarea name={name_prefix + '[filetype][]'} rows={filetype.split('\n').length + 2} cols="18" value={filetype} data-type="filetype" onChange={this.onChange} />
				</div>

				{defaults.length > 0 && (
					<div className="litespeed-col-auto">
						<div className="litespeed-desc">{litespeed_data['lang']['default_value']}:</div>
						<textarea readOnly rows={defaultsRows} cols={defaultsCols} value={defaults.join('\n')} />
						{(missing.length > 0 || inconsistent.length > 0) && (
							<div className="litespeed-defaults-flags">
								{missing.map((ext) => (
									<span key={'m-' + ext} className="litespeed-defaults-flag litespeed-defaults-flag--missing" title="Missing from your list">
										{ext}
									</span>
								))}
								{inconsistent.map((ext) => (
									<span
										key={'i-' + ext}
										className="litespeed-defaults-flag litespeed-defaults-flag--inconsistent"
										title="In your list, but the matching CDN include toggle is OFF — CDN will still rewrite this extension"
									>
										{ext}
									</span>
								))}
							</div>
						)}
						{missing.length > 0 && (
							<div>
								<a
									href="#"
									className="litespeed-defaults-add-link"
									onClick={(e) => {
										e.preventDefault();
										this.addMissingDefaults();
									}}
								>
									+ {litespeed_data['lang']['add_missing_defaults']} ({missing.length})
								</a>
							</div>
						)}
					</div>
				)}

				<div className="litespeed-col-auto">
					<button type="button" className="button button-link litespeed-collection-button litespeed-danger" onClick={this.delRow}>
						<span className="dashicons dashicons-dismiss"></span>
						<span className="screen-reader-text">{litespeed_data['lang']['cdn_mapping_remove']}</span>
					</button>
				</div>
			</div>
		);
	}
}
