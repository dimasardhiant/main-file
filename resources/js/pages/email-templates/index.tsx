import { useState } from 'react'
import { Head, router } from '@inertiajs/react'
import { PageTemplate } from '@/components/page-template'
import { Button } from '@/components/ui/button'
import { Pagination } from '@/components/ui/pagination'
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { Search, Eye } from 'lucide-react'
import { useTranslation } from 'react-i18next'

interface EmailTemplate {
  id: number
  name: string
  from: string
  created_at: string
  email_template_langs: Array<{
    id: number
    lang: string
    subject: string
  }>
}

interface Props {
  templates: {
    data: EmailTemplate[]
    from: number
    to: number
    total: number
    links: Array<{
      url: string | null
      label: string
      active: boolean
    }>
  }
  filters: {
    search?: string
    sort_field?: string
    sort_direction?: string
    per_page?: number
  }
}

export default function EmailTemplatesIndex({ templates, filters: pageFilters = {} }: Props) {
  const { t } = useTranslation()
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '')

  const handleAction = (action: string, item: EmailTemplate) => {
    if (action === 'view') {
      router.get(route('email-templates.show', item.id))
    }
  }

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault()
    applyFilters()
  }

  const applyFilters = () => {
    const params: any = { page: 1 }
    
    if (searchTerm) {
      params.search = searchTerm
    }
    
    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page
    }
    
    router.get(route('email-templates.index'), params, { preserveState: true, preserveScroll: true })
  }

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc'
    
    const params: any = { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1 
    }
    
    if (searchTerm) {
      params.search = searchTerm
    }
    
    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page
    }
    
    router.get(route('email-templates.index'), params, { preserveState: true, preserveScroll: true })
  }



  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Email Templates') }
  ]

  const columns = [
    { 
      key: 'name', 
      label: t('Name'), 
      sortable: true
    }
  ]

  return (
    <PageTemplate 
      title={t('Email Templates')} 
      url={route('email-templates.index')}
      breadcrumbs={breadcrumbs}
      noPadding
    >
      <Head title="Email Templates" />
      
      {/* Search section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={[]}
          showFilters={false}
          setShowFilters={() => {}}
          hasActiveFilters={() => false}
          activeFilterCount={() => 0}
          onResetFilters={() => {}}
          currentPerPage={pageFilters.per_page?.toString() || "10"}
          onPerPageChange={(value) => {
            const params: any = { page: 1, per_page: parseInt(value) }
            
            if (searchTerm) {
              params.search = searchTerm
            }
            
            router.get(route('email-templates.index'), params, { preserveState: true, preserveScroll: true })
          }}
        />
      </div>

      {/* Content section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                {columns.map((column) => (
                  <th 
                    key={column.key} 
                    className="px-4 py-3 text-left font-medium text-gray-500 cursor-pointer hover:bg-gray-100"
                    onClick={() => column.sortable && handleSort(column.key)}
                  >
                    <div className="flex items-center">
                      {column.label}
                      {column.sortable && (
                        <span className="ml-1">
                          {pageFilters.sort_field === column.key ? (
                            pageFilters.sort_direction === 'asc' ? '↑' : '↓'
                          ) : ''}
                        </span>
                      )}
                    </div>
                  </th>
                ))}
                <th className="px-4 py-3 text-right font-medium text-gray-500">
                  {t("Actions")}
                </th>
              </tr>
            </thead>
            <tbody>
              {templates?.data?.map((template: EmailTemplate) => (
                <tr key={template.id} className="border-b hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                  <td className="px-4 py-3">
                    <div className="font-medium">{template.name}</div>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Tooltip>
                      <TooltipTrigger asChild>
                        <Button 
                          variant="ghost" 
                          size="icon" 
                          onClick={() => handleAction('view', template)}
                          className="text-blue-500 hover:text-blue-700"
                        >
                          <Eye className="h-4 w-4" />
                        </Button>
                      </TooltipTrigger>
                      <TooltipContent>{t('View')}</TooltipContent>
                    </Tooltip>
                  </td>
                </tr>
              ))}
              
              {(!templates?.data || templates.data.length === 0) && (
                <tr>
                  <td colSpan={columns.length + 1} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                    {t("No email templates found")}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination section */}
        <Pagination
          from={templates?.from || 0}
          to={templates?.to || 0}
          total={templates?.total || 0}
          links={templates?.links}
          entityName={t("templates")}
          onPageChange={(url) => router.get(url)}
        />
      </div>
    </PageTemplate>
  )
}