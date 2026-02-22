// pages/hr/performance/employee-reviews/create.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';

export default function CreateEmployeeReview() {
  const { t } = useTranslation();
  const { employees, reviewCycles } = usePage().props as any;
  
  // State
  const [formData, setFormData] = useState({
    employee_id: '',
    reviewer_id: '',
    review_cycle_id: '',
    review_date: '',
    status: 'scheduled'
  });
  const [errors, setErrors] = useState<any>({});
  
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    // Clear error when field is edited
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };
  
  const handleSelectChange = (name: string, value: string) => {
    setFormData(prev => ({ ...prev, [name]: value }));
    // Clear error when field is edited
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Validate form
    const validationErrors: any = {};
    if (!formData.employee_id) {
      validationErrors.employee_id = t('Employee is required');
    }
    if (!formData.reviewer_id) {
      validationErrors.reviewer_id = t('Reviewer is required');
    }
    if (!formData.review_cycle_id) {
      validationErrors.review_cycle_id = t('Review cycle is required');
    }
    if (!formData.review_date) {
      validationErrors.review_date = t('Review date is required');
    }
    
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      return;
    }
    
    toast.loading(t('Scheduling employee review...'));
    
    router.post(route('hr.performance.employee-reviews.store'), formData, {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        }
        router.visit(route('hr.performance.employee-reviews.index'));
      },
      onError: (errors) => {
        toast.dismiss();
        setErrors(errors);
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to schedule employee review'));
        }
      }
    });
  };
  
  const handleCancel = () => {
    router.visit(route('hr.performance.employee-reviews.index'));
  };
  
  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.performance.employee-reviews.index') },
    { title: t('Performance'), href: route('hr.performance.employee-reviews.index') },
    { title: t('Employee Reviews'), href: route('hr.performance.employee-reviews.index') },
    { title: t('Schedule Review') }
  ];

  return (
    <PageTemplate 
      title={t("Schedule Employee Review")} 
      url="/hr/performance/employee-reviews/create"
      breadcrumbs={breadcrumbs}
    >
      <form onSubmit={handleSubmit}>
        <Card>
          <CardHeader>
            <CardTitle>{t('Schedule New Review')}</CardTitle>
            <CardDescription>{t('Create a new performance review for an employee')}</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="employee_id">{t('Employee')} <span className="text-red-500">*</span></Label>
                <Select 
                  value={formData.employee_id} 
                  onValueChange={(value) => handleSelectChange('employee_id', value)}
                >
                  <SelectTrigger className={errors.employee_id ? 'border-red-500' : ''}>
                    <SelectValue placeholder={t('Select employee')} />
                  </SelectTrigger>
                  <SelectContent searchable={true}>
                    {employees.map((employee: any) => (
                      <SelectItem key={employee.id} value={employee.id.toString()}>
                        {`${employee.name} (${employee.employee_id})`}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.employee_id && <p className="text-red-500 text-sm">{errors.employee_id}</p>}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="reviewer_id">{t('Reviewer')} <span className="text-red-500">*</span></Label>
                <Select 
                  value={formData.reviewer_id} 
                  onValueChange={(value) => handleSelectChange('reviewer_id', value)}
                >
                  <SelectTrigger className={errors.reviewer_id ? 'border-red-500' : ''}>
                    <SelectValue placeholder={t('Select reviewer')} />
                  </SelectTrigger>
                  <SelectContent searchable={true}>
                    {employees.map((employee: any) => (
                      <SelectItem key={employee.id} value={employee.id.toString()}>
                        {`${employee.name} (${employee.employee_id})`}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.reviewer_id && <p className="text-red-500 text-sm">{errors.reviewer_id}</p>}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="review_cycle_id">{t('Review Cycle')} <span className="text-red-500">*</span></Label>
                <Select 
                  value={formData.review_cycle_id} 
                  onValueChange={(value) => handleSelectChange('review_cycle_id', value)}
                >
                  <SelectTrigger className={errors.review_cycle_id ? 'border-red-500' : ''}>
                    <SelectValue placeholder={t('Select review cycle')} />
                  </SelectTrigger>
                  <SelectContent searchable={true}>
                    {reviewCycles.map((cycle: any) => (
                      <SelectItem key={cycle.id} value={cycle.id.toString()}>
                        {cycle.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.review_cycle_id && <p className="text-red-500 text-sm">{errors.review_cycle_id}</p>}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="review_date">{t('Review Date')} <span className="text-red-500">*</span></Label>
                <Input 
                  id="review_date" 
                  name="review_date" 
                  type="date" 
                  value={formData.review_date} 
                  onChange={handleInputChange} 
                  className={errors.review_date ? 'border-red-500' : ''}
                />
                {errors.review_date && <p className="text-red-500 text-sm">{errors.review_date}</p>}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="status">{t('Status')}</Label>
                <Select 
                  value={formData.status} 
                  onValueChange={(value) => handleSelectChange('status', value)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder={t('Select status')} />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="scheduled">{t('Scheduled')}</SelectItem>
                    <SelectItem value="in_progress">{t('In Progress')}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </CardContent>
          <CardFooter className="flex justify-between">
            <Button type="button" variant="outline" onClick={handleCancel}>
              {t('Cancel')}
            </Button>
            <Button type="submit">
              {t('Schedule Review')}
            </Button>
          </CardFooter>
        </Card>
      </form>
    </PageTemplate>
  );
}