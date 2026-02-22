// pages/hr/performance/employee-reviews/conduct.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, Star } from 'lucide-react';

export default function ConductEmployeeReview() {
  const { t } = useTranslation();
  const { review, indicators } = usePage().props as any;
  
  // State
  const [ratings, setRatings] = useState(
    indicators.map((indicator: any) => ({
      indicator_id: indicator.id,
      rating: indicator.rating || 3, // Default to 3 if no rating exists
      comments: indicator.comments || ''
    }))
  );
  const [overallComments, setOverallComments] = useState('');
  const [errors, setErrors] = useState<any>({});
  
  const handleRatingChange = (index: number, value: number) => {
    setRatings(prev => {
      const newRatings = [...prev];
      newRatings[index] = { ...newRatings[index], rating: value };
      return newRatings;
    });
  };
  
  const handleCommentsChange = (index: number, value: string) => {
    setRatings(prev => {
      const newRatings = [...prev];
      newRatings[index] = { ...newRatings[index], comments: value };
      return newRatings;
    });
  };
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Validate form - all indicators must have a rating
    const validationErrors: any = {};
    ratings.forEach((rating, index) => {
      if (!rating.rating) {
        validationErrors[`ratings.${index}.rating`] = t('Rating is required');
      }
    });
    
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      return;
    }
    
    toast.loading(t('Submitting review...'));
    
    router.post(route('hr.performance.employee-reviews.submit-ratings', review.id), {
      ratings,
      overall_comments: overallComments
    }, {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        }
        router.visit(route('hr.performance.employee-reviews.show', review.id));
      },
      onError: (errors) => {
        toast.dismiss();
        setErrors(errors);
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to submit review'));
        }
      }
    });
  };
  
  const handleBack = () => {
    router.visit(route('hr.performance.employee-reviews.show', review.id));
  };
  
  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.performance.indicator-categories.index') },
    { title: t('Performance'), href: route('hr.performance.indicator-categories.index') },
    { title: t('Employee Reviews'), href: route('hr.performance.employee-reviews.index') },
    { title: t('Conduct Review') }
  ];

  // Group indicators by category
  const indicatorsByCategory = indicators.reduce((acc: any, indicator: any) => {
    const categoryName = indicator.category || 'Uncategorized';
    
    if (!acc[categoryName]) {
      acc[categoryName] = [];
    }
    
    acc[categoryName].push(indicator);
    return acc;
  }, {});

  return (
    <PageTemplate 
      title={t("Conduct Performance Review")} 
      url={`/hr/performance/employee-reviews/${review.id}/conduct`}
      breadcrumbs={breadcrumbs}
    >
      <form onSubmit={handleSubmit}>
        <div className="space-y-6">
          {/* Actions */}
          <div className="flex justify-between">
            <Button type="button" variant="outline" onClick={handleBack}>
              <ArrowLeft className="h-4 w-4 mr-2" />
              {t('Back to Review')}
            </Button>
          </div>
          
          {/* Review Details */}
          <Card>
            <CardHeader>
              <CardTitle>{t('Review Information')}</CardTitle>
              <CardDescription>{t('You are conducting a performance review for:')}</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                  <div>
                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Employee')}</h3>
                    <p className="mt-1 text-base font-semibold">
                      {review.employee?.name}
                    </p>
                  </div>
                  
                  <div>
                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Review Cycle')}</h3>
                    <p className="mt-1 text-base font-semibold">
                      {review.review_cycle?.name}
                    </p>
                  </div>
                </div>
                
                <div className="space-y-4">
                  <div>
                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Review Date')}</h3>
                    <p className="mt-1 text-base font-semibold">
                      {review.review_date ? (window.appSettings?.formatDateTimeSimple(review.review_date, false) || new Date(review.review_date).toLocaleString()) : '-'  }
                    </p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
          
          {/* Rating Form */}
          <Card>
            <CardHeader>
              <CardTitle>{t('Performance Ratings')}</CardTitle>
              <CardDescription>{t('Rate the employee on each performance indicator')}</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-8">
                {Object.entries(indicatorsByCategory).map(([category, categoryIndicators]: [string, any]) => (
                  <div key={category} className="space-y-4">
                    <h3 className="font-medium text-lg">{category}</h3>
                    <div className="space-y-6">
                      {categoryIndicators.map((indicator: any, index: number) => {
                        const ratingIndex = ratings.findIndex(r => r.indicator_id === indicator.id);
                        
                        return (
                          <div key={indicator.id} className="border rounded-md p-4">
                            <div className="space-y-4">
                              <div>
                                <h4 className="font-medium">{indicator.name}</h4>
                                <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                  {indicator.description || t('No description')}
                                </p>
                                <div className="flex flex-wrap gap-2 mt-2">
                                  {indicator.measurement_unit && (
                                    <Badge variant="outline">
                                      {t('Measurement')}: {indicator.measurement_unit}
                                    </Badge>
                                  )}
                                  {indicator.target_value && (
                                    <Badge variant="outline">
                                      {t('Target')}: {indicator.target_value}
                                    </Badge>
                                  )}
                                  <Badge variant="outline">
                                    {t('Weight')}: {indicator.weight}
                                  </Badge>
                                </div>
                              </div>
                              
                              <div className="space-y-2">
                                <div className="flex justify-between items-center">
                                  <span className="text-sm font-medium">{t('Rating')}</span>
                                  <div className="flex items-center">
                                    <span className="text-lg font-bold mr-1">{ratings[ratingIndex]?.rating}</span>
                                    <Star className="h-4 w-4 fill-yellow-400 text-yellow-400" />
                                  </div>
                                </div>
                                <Input
                                  type="range"
                                  min={1}
                                  max={5}
                                  step={0.5}
                                  value={ratings[ratingIndex]?.rating}
                                  onChange={(e) => handleRatingChange(ratingIndex, parseFloat(e.target.value))}
                                  className="w-full"
                                />
                                <div className="flex justify-between text-xs text-gray-500">
                                  <span>{t('Poor')}</span>
                                  <span>{t('Average')}</span>
                                  <span>{t('Excellent')}</span>
                                </div>
                              </div>
                              
                              <div className="space-y-2">
                                <label htmlFor={`comments-${indicator.id}`} className="text-sm font-medium">
                                  {t('Comments')}
                                </label>
                                <Textarea
                                  id={`comments-${indicator.id}`}
                                  value={ratings[ratingIndex]?.comments}
                                  onChange={(e) => handleCommentsChange(ratingIndex, e.target.value)}
                                  placeholder={t('Add specific feedback for this indicator')}
                                  rows={2}
                                />
                              </div>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  </div>
                ))}
                
                <div className="pt-4 border-t">
                  <div className="space-y-2">
                    <label htmlFor="overall-comments" className="text-sm font-medium">
                      {t('Overall Comments')}
                    </label>
                    <Textarea
                      id="overall-comments"
                      value={overallComments}
                      onChange={(e) => setOverallComments(e.target.value)}
                      placeholder={t('Add overall feedback and recommendations')}
                      rows={4}
                    />
                  </div>
                </div>
              </div>
            </CardContent>
            <CardFooter className="flex justify-between">
              <Button type="button" variant="outline" onClick={handleBack}>
                {t('Cancel')}
              </Button>
              <Button type="submit">
                {t('Submit Review')}
              </Button>
            </CardFooter>
          </Card>
        </div>
      </form>
    </PageTemplate>
  );
}