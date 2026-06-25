import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Notification {
  type: 'success' | 'error' | 'warning';
  message: string;
}

export interface AdminNotificationData {
  note_id?: string;
  client_id?: string;
  client_name?: string;
  employee_name?: string;
  excerpt?: string;
}

export interface AdminNotificationItem {
  id: string;
  type: string;
  title: string;
  message: string;
  action_url?: string | null;
  read_at?: string | null;
  created_at: string;
  data: AdminNotificationData;
}

export interface AdminNotificationsPayload {
  count: number;
  items: AdminNotificationItem[];
  readCount: number;
  readItems: AdminNotificationItem[];
}

export interface Auth {
  user: User;
  employee?: Employee;
}

export interface Option {
  label: string
  value: string
  icon?: string
}

export interface BreadcrumbItem {
  title: string;
  href: string;
}

export interface NavItemsGroup {
  title: string;
  icon?: LucideIcon;
  href?: string;
  items?: NavItem[];
}

export interface NavItem {
  title: string;
  href: string;
  icon?: LucideIcon;
  isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
  name: string;
  auth: Auth;
  adminNotifications?: AdminNotificationsPayload | null;
  ziggy: Config & { location: string };
  sidebarOpen: boolean;
};

export interface User {
  id: string;
  name: string;
  email: string;
  password?: string;
  password_confirmation?: string;
  is_active: boolean;
  employee?: Employee;
  avatar?: string;
}

export interface Paginated<T> {
  data: T[];
  links: any[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface Column<T = any> {
  key: keyof T | string
  label: string
  align?: Align
  class?: string
  headerClass?: string
  sortable?: boolean
  format?: string
  link?: (row: T) => string | undefined
}

export interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface TimeRecord {
  id: string
  Employee: Employee
  employee_id: string
  date_start: string
  date_end: string
  time_start: string
  time_end: string
}

export interface File {
  name: string
  created_at: string
  url: string
}

export interface TabItem {
  title: string;
  href: string;
}

export interface Note {
  id: string
  user: User
  content: string
  type: 'general' | 'incident'
  employee_time_record_id?: string | null
  employee_time_record?: EmployeeTimeRecordLinked | null
  created_at: string
}

export interface EmployeeStatusPeriod {
  id: string
  type: 'vacation' | 'sick_leave' | 'absence' | 'permission'
  label: string
  color: string
  start_at: string
  end_at: string
  start_at_formatted: string
  end_at_formatted: string
  start_at_input: string
  end_at_input: string
  updated_at: string
  updated_at_formatted: string
  notes?: string | null
  updated_by?: User | null
}

export interface EmployeeStatus {
  code: 'active' | 'vacation' | 'sick_leave' | 'absence' | 'permission'
  label: string
}

export interface Announcement {
  id: string
  title: string
  image?: string | null
  content: string
  created_at?: string
}

export interface EmployeeTimeRecordLinked {
  id: string
  date_in: string
  date_out?: string | null
  time_in: string
  time_out?: string | null
  employee?: Employee
  assigned_hour?: AssignedHour
  notes?: Note[]
}

export interface Service {
  id: string
  name: string
  icon: string
  icon_slug: string
  color: string
  description: string
  price: number
  discount_partner: string | number
  tasks?: Task[]
}

export interface Task {
  id: string
  name: string
}

export interface AvailableHour {
  id: string
  day_of_week: number
  time_start: string
  time_end: string
}

export interface Invoice {
  id: string
  client: Client
  date: string
  due_date: string
  subtotal?: number
  discount?: number
  tax?: number
  total?: number
  lines?: InvoiceLine[]
}

export interface InvoiceLine {
  id?: string
  invoice_id?: string
  concept: string
  price: number
  quantity: number
  discount: number
  tax_type: string
  subtotal: number
}

export interface Budget {
  id: string
  client_id: string
  client?: Client
  client_name?: string
  status_name: string
  status: string
  due_date: string
  content: string
  subtotal: number
  discount: number
  tax: number
  total: number
  created_at: string
  lines: BudgetLine[]
}

export interface BudgetLine {
  id?: string
  budget_id?: string
  concept: string
  price: number
  quantity: number
  discount: number
  tax_type: string
  subtotal: number
}

export interface Contract {
  id: string
  title: string
  client_id: string
  client: Client
  status_name: string
  status: string
  content: string
  date_start: string
  date_end: string
}

export interface Gender {
  id: string
  name: string
}

export interface Country {
  id: string
  name: string
}

export interface Client {
  id: string
  name: string
  birth_date?: string
  gender_id: string
  gender: Gender
  cif_nif: string
  email: string
  phone: string
  phone_2?: string
  address: string
  city: string
  zip_code: string
  country_id: string
  country: Country
  bank_name: string
  bank_account: string
  tax_included: boolean
  is_partner: boolean
  automatic_invoice: boolean
  deleted_at?: string
  files?: File[]
  notes?: Note[]
  services?: Service[]
  assigned_hours_templates?: AssignedHourTemplate[]
  assigned_characteristics?: CharacteristicOption[]
  assigned_hours?: AssignedHour[]
}

export interface ClientOption {
  value: number
  label: string
}

export interface ServiceOption {
  id: string
  name: string
}

export interface Color {
  id: string
  name: string
}

export interface Icon {
  name: string
  svg: string
}

export interface Status {
  id: string
  name: string
}

export interface ItemTemplateOption {
  value: string
  label: string
}

export interface BudgetTemplate {
  id: string
  name: string
  content: string
}

export interface ContractTemplate {
  id: string
  name: string
  content: string
}

export interface Employee {
  id: string
  name: string
  birth_date?: string
  hire_date?: string
  gender_id: string
  gender: Gender
  nif: string
  email: string
  phone: string
  phone_2?: string
  address: string
  city: string
  zip_code: string
  country_id: string
  country: Country
  files?: Array<File>
  notes?: Array<Note>
  services?: Array<Service>
  user: User
  assigned_hours?: AssignedHour[]
  assigned_characteristics?: CharacteristicOption[]
  status_periods?: EmployeeStatusPeriod[]
  is_selectable?: boolean
  unavailability_reason?: string | null
  blocking_status_period?: EmployeeStatusPeriod | null
}

export interface Characteristic {
  id: string
  name: string
  options?: CharacteristicOption[]
}

export interface CharacteristicOption {
  id: string
  characteristic?: Characteristic
  name: string
}

export interface CharacteristicOptionsGrouped {
  name: string
  options: CharacteristicOption[] | []
}

export interface IconOption {
  slug: string
  svg: string
}

export interface Audit {
  id: string
  user: User
  action: string
  resource: string
  resource_name: string
  old_values: Any
  new_values: Any
  created_at: string
  created_at_formatted: string
  trans_prefix: string
}

export interface FieldBinding {
  id?: string | null
  name: string
  'onUpdate:modelValue': (v: any) => void
  defaultValue?: any
  error?: string
}

export interface AssignedHour {
  id: string
  assigned_hours_template_id?: string
  client_id?: string
  employee_id: string
  employee_substitute_id?: string | null
  service_id: string
  employee: Employee
  client: Client
  service: Service
  date: string
  time_start: string
  time_end: string
  time_records_count?: number
  programmed_hours_formatted?: string
  registered_hours_formatted?: string
}

export interface AssignedHourTemplate {
  id: string
  employee_id: string
  service_id: string
  employee: Employee
  service: Service
  date: string
  date_start: string
  date_end: string
  time_start: string
  time_end: string
  recurrency: string
  days_of_week: string[]
}

export interface Work {
  id: string
  service: Service
  client: Client
  date: string
  time_start: string
  time_end: string
  assigned_hour?: AssignedHour
}

export interface Configuration {
  id: string
  company_name: string
  company_cif_nif: string
  company_email: string
  company_phone: string
  company_address: string
  company_city: string
  company_zip_code: string
  company_country_id: string
  company_image: string
}

export type SelectOption = { label: string; value: string }

export type FieldBinder = (key: string) => FieldBinding

export type AnyItem = Record<string, any>

export type AnyErrors = Record<string, string>

export type ItemTemplateFn<T> = () => T

export type Align = 'left' | 'center' | 'right'

export type SortDir = 'asc' | 'desc' | null

export type SortKey = string | null
