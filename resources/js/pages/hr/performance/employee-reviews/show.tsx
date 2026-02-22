// pages/hr/performance/employee-reviews/show.tsx
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from 'react-i18next';
import { ClipboardList, ArrowLeft, Star } from 'lucide-react';
import { hasPermission } from '@/utils/authorization';

export default function ShowEmployeeReview() {
  const { t } = useTranslation();
  const { review, auth } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'scheduled':
        return <Badge variant="outline" className="bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20">{t('Scheduled')}</Badge>;
      case 'in_progress':
        return <Badge variant="outline" className="bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20">{t('In Progress')}</Badge>;
      case 'completed':
        return <Badge variant="outline" className="bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">{t('Completed')}</Badge>;
      default:
        return <Badge variant="outline">{status}</Badge>;
    }
  };

  const handleConductReview = () => {
    router.visit(route('hr.performance.employee-reviews.conduct', review.id));
  };

  const handleBack = () => {
    router.visit(route('hr.performance.employee-reviews.index'));
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.performance.indicator-categories.index') },
    { title: t('Performance'), href: route('hr.performance.indicator-categories.index') },
    { title: t('Employee Reviews'), href: route('hr.performance.employee-reviews.index') },
    { title: t('View Review') }
  ];

  // Group ratings by category
  const ratingsByCategory = review.ratings?.reduce((acc: any, rating: any) => {
    const categoryId = rating.indicator?.category?.id || 'uncategorized';
    const categoryName = rating.indicator?.category?.name || 'Uncategorized';

    if (!acc[categoryId]) {
      acc[categoryId] = {
        name: categoryName,
        ratings: []
      };
    }

    acc[categoryId].ratings.push(rating);
    return acc;
  }, {});

  return (
    <PageTemplate
      title={t("Review Details")}
      url={`/hr/performance/employee-reviews/${review.id}`}
      breadcrumbs={breadcrumbs}
    >
      <div className="space-y-6">
        {/* Actions */}
        <div className="flex justify-between">
          <Button variant="outline" onClick={handleBack}>
            <ArrowLeft className="h-4 w-4 mr-2" />
            {t('Back to Reviews')}
          </Button>

          {review.status !== 'completed' && hasPermission(permissions, 'edit-employee-reviews') && (
            <Button onClick={handleConductReview}>
              <ClipboardList className="h-4 w-4 mr-2" />
              {t('Conduct Review')}
            </Button>
          )}
        </div>

        {/* Review Details */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Review Information')}</CardTitle>
            <CardDescription>{t('Details about this performance review')}</CardDescription>
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
                  <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Reviewer')}</h3>
                  <p className="mt-1 text-base font-semibold">
                    {review.reviewer?.name}
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
                    {review.review_date ? (window.appSettings?.formatDateTimeSimple(review.review_date, false) || new Date(review.review_date).toLocaleString()) : '-'}
                  </p>
                </div>

                <div>
                  <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Status')}</h3>
                  <div className="mt-1">
                    {getStatusBadge(review.status)}
                  </div>
                </div>
              </div>
            </div>

            {review.status === 'completed' && (
              <div className="mt-6 pt-6 border-t">
                <div className="flex items-center justify-between">
                  <h3 className="text-lg font-medium">{t('Overall Rating')}</h3>
                  <div className="flex items-center">
                    <span className="text-2xl font-bold mr-2">{review.overall_rating?.toFixed(1)}</span>
                    <Star className="h-5 w-5 fill-yellow-400 text-yellow-400" />
                  </div>
                </div>

                {review.comments && (
                  <div className="mt-4">
                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Comments')}</h3>
                    <p className="mt-1 text-base">{review.comments}</p>
                  </div>
                )}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Ratings */}
        {review.status === 'completed' && (
          <Card>
            <CardHeader>
              <CardTitle>{t('Performance Ratings')}</CardTitle>
              <CardDescription>{t('Individual ratings for each performance indicator')}</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-6">
                {Object.values(ratingsByCategory || {}).map((category: any) => (
                  <div key={category.name} className="space-y-4">
                    <h3 className="font-medium text-lg">{category.name}</h3>
                    <div className="space-y-4">
                      {category.ratings.map((rating: any) => (
                        <div key={rating.id} className="border rounded-md p-4">
                          <div className="flex items-start justify-between">
                            <div>
                              <h4 className="font-medium">{rating.indicator?.name}</h4>
                              <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {rating.indicator?.description || t('No description')}
                              </p>
                              {rating.indicator?.measurement_unit && (
                                <Badge variant="outline" className="mt-2">
                                  {rating.indicator?.measurement_unit}
                                </Badge>
                              )}
                            </div>
                            <div className="flex items-center">
                              <span className="text-xl font-bold mr-2">{rating.rating.toFixed(1)}</span>
                              <Star className="h-5 w-5 fill-yellow-400 text-yellow-400" />
                            </div>
                          </div>

                          {rating.comments && (
                            <div className="mt-3 pt-3 border-t">
                              <h5 className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Comments')}</h5>
                              <p className="mt-1 text-sm">{rating.comments}</p>
                            </div>
                          )}
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </PageTemplate>
  );
}