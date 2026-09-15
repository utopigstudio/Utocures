/** Each application screen links to the guide for its actual workflow. */
export function helpArticleFor(component: string, role: string | null): string | undefined {
  const section = component.split('/')[0];
  if (section === 'help') return undefined;
  if (role === 'employee') {
    if (component === 'employees/Announcements') return 'announcement-board';
    if (component === 'employees/HoursWorked') return 'employee-hours';
    if (['EmployeeDashboard', 'employees/Schedule', 'employees/ScheduleMonth'].includes(component)) return 'employee-agenda';
    return 'employee-work';
  }
  if (role === 'client') {
    if (section === 'messages') return 'messages';
    if (component === 'clients/Announcements') return 'announcement-board';
    return 'client-area';
  }
  const articles: Record<string, string> = {
    Dashboard: 'admin-start',
    clients: 'clients',
    employees: 'employees',
    services: 'services-characteristics',
    characteristics: 'characteristics',
    budgets: 'budgets',
    contracts: 'contracts',
    invoices: 'invoices',
    budgetTemplates: 'budget-templates',
    contractTemplates: 'templates',
    employeeDocuments: 'employee-documents',
    employeeTimeRecords: 'time-records',
    announcements: 'announcements',
    messages: 'messages',
    resources: 'resources',
    users: 'users',
    configuration: 'configuration',
    audits: 'audits',
  };
  if (component === 'clients/Notes') return 'notes';
  if (component === 'clients/Budgets') return 'budgets';
  if (component === 'clients/Contracts') return 'contracts';
  if (component === 'clients/Invoices') return 'invoices';
  return articles[section];
}
