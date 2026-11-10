import React, { useEffect, useRef, useState } from 'react'
import { Modal } from 'bootstrap'
import { request } from './api'

const blank = () => ({ name: '', employee_id: '', email: '', role_id: '', area: '', region: '', zone: '', joining_date: new Date().toLocaleDateString('en-CA'), status: 'active', password: '' })
const dateLabel = value => value ? new Intl.DateTimeFormat('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value.slice(0, 10) + 'T00:00:00')) : '-'
const fields = [['name', 'Name', 'text', 100], ['employee_id', 'Employee ID', 'text', 50], ['email', 'Email address', 'email', 255], ['joining_date', 'Joining date', 'date'], ['area', 'Area', 'text', 100], ['region', 'Region', 'text', 100], ['zone', 'Zone', 'text', 100]]

function Dialog({ title, children, close, busy }) {
  const ref = useRef(null)
  const closeRef = useRef(close)
  closeRef.current = close
  useEffect(() => {
    const element = ref.current
    const previous = document.activeElement
    const modal = new Modal(element, { backdrop: 'static', keyboard: false })
    const hidden = () => { closeRef.current(); previous?.focus() }
    element.addEventListener('hidden.bs.modal', hidden)
    modal.show()
    return () => {
      element.removeEventListener('hidden.bs.modal', hidden)
      modal.dispose()
      document.querySelectorAll('.modal-backdrop').forEach(el => el.remove())
      document.body.classList.remove('modal-open')
      document.body.style.removeProperty('overflow')
      document.body.style.removeProperty('padding-right')
    }
  }, [])
  const dismiss = () => { if (!busy) Modal.getInstance(ref.current)?.hide() }
  return <div ref={ref} className="modal" tabIndex="-1" aria-labelledby="dialog-title" onKeyDown={e => { if (e.key === 'Escape') dismiss() }}>
    <div className="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div className="modal-content">
      <div className="modal-header"><h2 className="modal-title fs-5" id="dialog-title">{title}</h2><button className="btn-close" aria-label="Close dialog" disabled={busy} onClick={dismiss} /></div>
      {children(dismiss)}
    </div></div>
  </div>
}

export default function App() {
  const [result, setResult] = useState(null)
  const [roles, setRoles] = useState([])
  const [roleError, setRoleError] = useState('')
  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')
  const [page, setPage] = useState(1)
  const [size, setSize] = useState(10)
  const [reload, setReload] = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')
  const [dialog, setDialog] = useState(null)
  const [form, setForm] = useState(blank)
  const [errors, setErrors] = useState({})
  const [formError, setFormError] = useState('')
  const [busy, setBusy] = useState(false)
  const [showPassword, setShowPassword] = useState(false)
  const [statusPending, setStatusPending] = useState('')
  const [statusError, setStatusError] = useState('')

  useEffect(() => {
    const controller = new AbortController()
    request('/roles', { signal: controller.signal }).then(data => { setRoles(data.data); setRoleError('') }).catch(e => { if (e.name !== 'AbortError') setRoleError(e.message) })
    return () => controller.abort()
  }, [reload])

  useEffect(() => {
    const controller = new AbortController()
    setLoading(true); setError('')
    request(`/employees?${new URLSearchParams({ page, per_page: size, search: query })}`, { signal: controller.signal })
      .then(data => {
        if (data.last_page < page) { setPage(data.last_page); return }
        setResult(data)
      }).catch(e => { if (e.name !== 'AbortError') setError(e.message) })
      .finally(() => { if (!controller.signal.aborted) setLoading(false) })
    return () => controller.abort()
  }, [page, size, query, reload])

  async function toggleStatus(employee) {
    if (statusPending) return
    setStatusPending(employee.route_key); setStatusError(''); setNotice('')
    try {
      const response = await request(`/employees/${employee.route_key}/status`, {
        method: 'PATCH', body: JSON.stringify({ status: employee.status === 'active' ? 'inactive' : 'active' }),
      })
      setResult(current => ({ ...current, data: current.data.map(item => item.route_key === employee.route_key ? response.data : item) }))
      setNotice(response.message)
    } catch (e) { setStatusError(e.message) }
    finally { setStatusPending('') }
  }

  function open(type, employee) {
    setErrors({}); setFormError(''); setShowPassword(false)
    setForm(employee ? { ...employee, role_id: employee.role?.route_key || '', joining_date: employee.joining_date?.slice(0, 10), password: '' } : blank())
    setDialog({ type, employee })
  }

  async function save(event, dismiss) {
    event.preventDefault(); setBusy(true); setErrors({}); setFormError('')
    try {
      const body = Object.fromEntries([...fields.map(([name]) => name), 'role_id', 'status', 'password'].map(key => [key, form[key]]))
      const response = await request(dialog.type === 'add' ? '/employees' : `/employees/${dialog.employee.route_key}`, { method: dialog.type === 'add' ? 'POST' : 'PUT', body: JSON.stringify(body) })
      setNotice(response.message)
      setReload(value => value + 1); dismiss()
    } catch (e) { setErrors(e.fields || {}); setFormError(Object.keys(e.fields || {}).length ? 'Please check the highlighted fields.' : e.message) }
    finally { setBusy(false) }
  }

  async function remove(dismiss) {
    setBusy(true); setFormError('')
    try {
      const response = await request(`/employees/${dialog.employee.route_key}`, { method: 'DELETE' })
      setNotice(response.message); setReload(value => value + 1); dismiss()
    } catch (e) { setFormError(e.message) } finally { setBusy(false) }
  }

  function control(name, label, type = 'text', maxLength) {
    return <div className="col-md-6" key={name}><label className="form-label" htmlFor={name}>{label} <span className="required">*</span></label>
      <input id={name} name={name} type={type} maxLength={maxLength} required className={`form-control ${errors[name] ? 'is-invalid' : ''}`} value={form[name]} onChange={e => setForm({ ...form, [name]: e.target.value })} aria-invalid={!!errors[name]} aria-describedby={errors[name] ? `${name}-error` : undefined} />
      {errors[name] && <div id={`${name}-error`} className="invalid-feedback">{errors[name][0]}</div>}
    </div>
  }

  return <><header className="topbar"><div className="container-fluid portal-width d-flex align-items-center gap-3"><span className="brand-mark" aria-hidden="true">EP</span><span className="brand-name">Employee Portal</span><span className="workspace-label ms-auto">People management</span></div></header>
    <main className="container-fluid portal-width py-5">
      <div className="page-heading d-flex justify-content-between align-items-center gap-3 mb-4"><div><h1>Employees</h1><p className="text-secondary mb-0">Your employee directory, all in one place.</p></div><button className="btn btn-primary" onClick={() => open('add')}>+ Add employee</button></div>
      {notice && <div className="alert alert-success alert-dismissible" role="status">{notice}<button className="btn-close" aria-label="Dismiss notification" onClick={() => setNotice('')} /></div>}
      {statusError && <div className="alert alert-danger" role="alert">{statusError}</div>}
      <section className="directory" aria-label="Employee directory">
        <div className="directory-toolbar"><div><h2 className="fs-6 mb-1">All employees</h2><span className="text-secondary small">{result ? `${result.total} ${query ? 'matching ' : ''}records` : 'Employee records'}</span></div>
          <form className="search-form d-flex gap-2" role="search" onSubmit={e => { e.preventDefault(); setPage(1); setQuery(search.trim()) }}><input className="form-control" aria-label="Search by name, employee ID or email" placeholder="Search name, ID or email" value={search} onChange={e => setSearch(e.target.value)} /><button className="btn btn-outline-secondary" type="submit">Search</button>{query && <button className="btn btn-link" type="button" onClick={() => { setSearch(''); setQuery(''); setPage(1) }}>Clear</button>}</form>
        </div>
        {error ? <div className="p-5 text-center" role="alert"><p>{error}</p><button className="btn btn-outline-primary" onClick={() => setReload(value => value + 1)}>Try again</button></div> : loading ? <div className="loading-state" role="status"><span className="spinner-border spinner-border-sm me-2" />Loading employees...</div> : <>
          <div className="table-responsive"><table className="table align-middle mb-0"><thead><tr>{['Sr. no.', 'Name', 'Employee ID', 'Email address', 'Joining date', 'Status', 'Actions'].map(label => <th scope="col" key={label}>{label}</th>)}</tr></thead><tbody>
            {result?.data.map((employee, index) => <tr key={employee.route_key}><td className="text-secondary">{result.from + index}</td><td><div className="employee-name">{employee.name}</div><div className="small text-secondary">{employee.role?.name || '-'}</div></td><td>{employee.employee_id}</td><td className="email-cell">{employee.email}</td><td className="text-nowrap">{dateLabel(employee.joining_date)}</td><td><div className="form-check form-switch mb-0"><input className="form-check-input" type="checkbox" role="switch" id={`status-${employee.route_key}`} checked={employee.status === 'active'} disabled={!!statusPending} onChange={() => toggleStatus(employee)} aria-label={`Active status for ${employee.name}`} /><label className="form-check-label small" htmlFor={`status-${employee.route_key}`}>{statusPending === employee.route_key ? 'Saving…' : employee.status === 'active' ? 'Active' : 'Inactive'}</label></div></td><td><div className="d-flex gap-1"><button className="btn btn-sm btn-light" aria-label={`View ${employee.name}`} onClick={() => open('view', employee)}>View</button><button className="btn btn-sm btn-light" aria-label={`Edit ${employee.name}`} onClick={() => open('edit', employee)}>Edit</button><button className="btn btn-sm btn-outline-danger" aria-label={`Delete ${employee.name}`} onClick={() => open('delete', employee)}>Delete</button></div></td></tr>)}
          </tbody></table></div>
          {!result?.data.length && <div className="empty-state"><h3 className="fs-5">{query ? 'No matching employees' : 'Your directory is ready'}</h3><p className="text-secondary">{query ? 'Try another name, employee ID or email address.' : 'Add your first employee to get started.'}</p><button className="btn btn-outline-primary" onClick={() => query ? (setSearch(''), setQuery(''), setPage(1)) : open('add')}>{query ? 'Clear search' : 'Add employee'}</button></div>}
        </>}
        <div className="directory-footer"><label className="d-flex align-items-center gap-2 small text-secondary">Rows per page<select className="form-select form-select-sm page-size" value={size} onChange={e => { setSize(Number(e.target.value)); setPage(1) }}>{[5, 10, 25, 50].map(n => <option key={n}>{n}</option>)}</select></label><div className="d-flex gap-3 align-items-center"><span className="small text-secondary">{result?.total ? `${result.from}-${result.to} of ${result.total}` : '0 records'}</span><button className="btn btn-sm btn-outline-secondary" disabled={loading || page <= 1} onClick={() => setPage(page - 1)}>Previous</button><button className="btn btn-sm btn-outline-secondary" disabled={loading || !result || page >= result.last_page} onClick={() => setPage(page + 1)}>Next</button></div></div>
      </section>
    </main>
    {dialog && <Dialog title={{ add: 'Add employee', edit: 'Edit employee', view: 'Employee details', delete: 'Delete employee' }[dialog.type]} busy={busy} close={() => setDialog(null)}>{dismiss => dialog.type === 'add' || dialog.type === 'edit' ? <form onSubmit={e => save(e, dismiss)}><div className="modal-body"><p className="small text-secondary">Fields marked * are required.</p>{formError && <div className="alert alert-danger" role="alert">{formError}</div>}<fieldset disabled={busy}><div className="row g-3">{fields.map(field => control(...field))}
      <div className="col-md-6"><label className="form-label" htmlFor="role_id">Role <span className="required">*</span></label><select id="role_id" required className={`form-select ${errors.role_id ? 'is-invalid' : ''}`} value={form.role_id} onChange={e => setForm({ ...form, role_id: e.target.value })}><option value="">Select a role</option>{roles.map(role => <option key={role.route_key} value={role.route_key}>{role.name}</option>)}</select>{errors.role_id && <div className="invalid-feedback">{errors.role_id[0]}</div>}{roleError ? <div className="text-danger small mt-1">{roleError} <button type="button" className="btn btn-link btn-sm" onClick={() => setReload(value => value + 1)}>Retry roles</button></div> : !roles.length && <div className="small text-secondary mt-1">No roles available yet. Check the API role seeder.</div>}</div>
      <div className="col-12"><label className="form-label" htmlFor="password">Password {dialog.type === 'add' && <span className="required">*</span>}</label><div className="input-group"><input id="password" type={showPassword ? 'text' : 'password'} className={`form-control ${errors.password ? 'is-invalid' : ''}`} autoComplete="new-password" required={dialog.type === 'add'} minLength={8} maxLength={255} value={form.password} onChange={e => setForm({ ...form, password: e.target.value })} /><button className="btn btn-outline-secondary" type="button" onClick={() => setShowPassword(!showPassword)}>{showPassword ? 'Hide' : 'Show'}</button></div>{errors.password && <div className="text-danger small mt-1">{errors.password[0]}</div>}<div className="form-text">{dialog.type === 'edit' ? 'Leave blank to keep the current password. ' : ''}At least 8 characters, including letters and numbers.</div></div>
      <div className="col-12"><div className="form-check form-switch"><input className="form-check-input" id="status" type="checkbox" role="switch" checked={form.status === 'active'} onChange={e => setForm({ ...form, status: e.target.checked ? 'active' : 'inactive' })} /><label className="form-check-label" htmlFor="status">Employee is {form.status}</label></div>{errors.status && <div className="text-danger small">{errors.status[0]}</div>}</div>
    </div></fieldset></div><div className="modal-footer"><button type="button" className="btn btn-outline-secondary" disabled={busy} onClick={dismiss}>Cancel</button><button className="btn btn-primary" disabled={busy || !roles.length}>{busy ? 'Saving...' : dialog.type === 'add' ? 'Add employee' : 'Save changes'}</button></div></form> : dialog.type === 'view' ? <><div className="modal-body"><dl className="details-grid">{[...fields.map(([name, label]) => [label, name === 'joining_date' ? dateLabel(form[name]) : form[name]]), ['Role', dialog.employee.role?.name], ['Status', form.status]].map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value || '-'}</dd></div>)}</dl></div><div className="modal-footer"><button className="btn btn-primary" onClick={dismiss}>Close</button></div></> : <><div className="modal-body">{formError && <div className="alert alert-danger" role="alert">{formError}</div>}<p>Delete <strong>{dialog.employee.name}</strong> ({dialog.employee.employee_id})?</p><p className="text-secondary mb-0">This removes the employee from the directory while retaining the record.</p></div><div className="modal-footer"><button className="btn btn-outline-secondary" disabled={busy} onClick={dismiss}>Cancel</button><button className="btn btn-danger" disabled={busy} onClick={() => remove(dismiss)}>{busy ? 'Deleting...' : 'Delete employee'}</button></div></>}</Dialog>}
  </>
}


