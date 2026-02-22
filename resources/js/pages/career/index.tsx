import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Search, MapPin, Clock, Users, Star, Filter, Building, ChevronLeft, ChevronRight, Briefcase, Target, Award } from 'lucide-react';
import CareerHeader from '@/components/career/CareerHeader';
import CareerFooter from '@/components/career/CareerFooter';
import { getImagePath } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';
import { useFavicon } from '@/hooks/use-favicon';
import { useBrandTheme } from '@/hooks/use-brand-theme';

export default function CareerPage() {
  const { t, i18n } = useTranslation();
  const { companySettings, jobPostings, jobTypes, locations, filters, companyId, vacancyRanges, companySlug, userSlug } = usePage().props as any;
  const [searchTerm, setSearchTerm] = useState(filters?.search || '');
  const [locationFilter, setLocationFilter] = useState(filters?.location || 'all');
  const [typeFilter, setTypeFilter] = useState(filters?.job_type ? filters.job_type.split(',') : []);
  const [salaryFilter, setSalaryFilter] = useState(filters?.salary_range || 'all');
  const [vacancyFilter, setVacancyFilter] = useState(filters?.vacancies ? filters.vacancies.split(',') : []);
  const [sortBy, setSortBy] = useState(filters?.sort || 'newest');
  const handleVacancyChange = (vacancyRange: string, checked: boolean) => {
    if (checked) {
      setVacancyFilter([...vacancyFilter, vacancyRange]);
    } else {
      setVacancyFilter(vacancyFilter.filter(range => range !== vacancyRange));
    }
  };

  const handleJobTypeChange = (jobTypeId: string, checked: boolean) => {
    if (checked) {
      setTypeFilter([...typeFilter, jobTypeId]);
    } else {
      setTypeFilter(typeFilter.filter(id => id !== jobTypeId));
    }
  };

  const handleSearch = () => {
    const params = {
      search: searchTerm,
      location: locationFilter !== 'all' ? locationFilter : undefined,
      job_type: typeFilter.length > 0 ? typeFilter.join(',') : undefined,
      salary_range: salaryFilter !== 'all' ? salaryFilter : undefined,
      vacancies: vacancyFilter.length > 0 ? vacancyFilter.join(',') : undefined,
      sort: sortBy,
    };
    
    if (userSlug) {
      router.get(route('career.index', userSlug), params, { preserveState: true });
    } else {
      router.get('/career', params, { preserveState: true });
    }
  };

  const [currentPage, setCurrentPage] = useState(1);
  const jobsPerPage = 6;

  // Use favicon hook like other pages
  useFavicon();
  useBrandTheme();

  // Use dynamic job data from controller
  const jobs = jobPostings?.data || [];
  const dynamicLocations = locations || [];
  const dynamicJobTypes = jobTypes || [];

  // Remove client-side filtering since it's now handled by controller
  const filteredJobs = jobs;
  const totalPages = jobPostings?.last_page || 1;
  const currentPageFromServer = jobPostings?.current_page || 1;
  const paginatedJobs = jobs;

  const handlePageChange = (page: number) => {
    const params = {
      search: searchTerm,
      location: locationFilter !== 'all' ? locationFilter : undefined,
      job_type: typeFilter.length > 0 ? typeFilter.join(',') : undefined,
      salary_range: salaryFilter !== 'all' ? salaryFilter : undefined,
      vacancies: vacancyFilter.length > 0 ? vacancyFilter.join(',') : undefined,
      sort: sortBy,
      page: page,
    };
    
    if (userSlug) {
      router.get(route('career.index', userSlug), params, { preserveState: true });
    } else {
      router.get('/career', params, { preserveState: true });
    }
  };


  return (
    <>
      <Head title={t("Careers - Join Our Team")}>
        {companySettings?.favIcon && (
          <>
            <link rel="icon" href={getImagePath(companySettings.favIcon)} />
            <link rel="shortcut icon" href={getImagePath(companySettings.favIcon)} />
            <link rel="apple-touch-icon" href={getImagePath(companySettings.favIcon)} />
          </>
        )}
      </Head>

      <div className="min-h-screen bg-gray-50">
        <CareerHeader logoOnly={true} companySettings={companySettings} />

        {/* Hero Section */}
        <section className="relative bg-primary/5 text-gray-800 py-16 overflow-hidden">
          {/* Background Pattern */}
          <div className="absolute inset-0 opacity-10">
            <div className="absolute top-10 left-10 w-20 h-20 bg-primary/20 rounded-full"></div>
            <div className="absolute top-32 right-20 w-16 h-16 bg-primary/15 rounded-full"></div>
            <div className="absolute bottom-20 left-1/4 w-12 h-12 bg-primary/25 rounded-full"></div>
            <div className="absolute bottom-32 right-1/3 w-8 h-8 bg-primary/30 rounded-full"></div>
          </div>

          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div className="text-center mb-12">
              <div className="inline-flex items-center gap-2 bg-primary/10 rounded-full px-4 py-2 mb-6">
                <Star className="h-4 w-4 text-primary" />
                <span className="text-sm font-medium text-primary">{t("Join 500+ Amazing Professionals")}</span>
              </div>

              <h1 className="text-4xl md:text-5xl font-light mb-6 text-gray-700">
                {t("Build Your Dream Career")}
              </h1>

              <p className="text-xl md:text-2xl mb-8 text-gray-600 max-w-3xl mx-auto leading-relaxed">
                {t("Discover exciting opportunities, grow with innovative projects, and make a meaningful impact in a collaborative environment")}
              </p>
            </div>

            {/* Stats */}
            <div className="mb-12">
            </div>
          </div>
        </section>

        {/* Filters and Jobs Section */}
        <section className="py-12">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {/* Header with job count and search */}
            <div className="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0 mb-8">
              <h2 className="text-xl md:text-2xl font-bold text-gray-900">
                {jobPostings?.total || 0} {t("Available Jobs")}
              </h2>
              <div className="flex flex-col space-y-3 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-4">
                <div className="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-2">
                  <span className="text-sm text-gray-600 whitespace-nowrap">{t("Sort by")}:</span>
                  <Select value={sortBy} onValueChange={(value) => {
                    setSortBy(value);
                    const params = {
                      search: searchTerm,
                      location: locationFilter !== 'all' ? locationFilter : undefined,
                      job_type: typeFilter.length > 0 ? typeFilter.join(',') : undefined,
                      salary_range: salaryFilter !== 'all' ? salaryFilter : undefined,
                      vacancies: vacancyFilter.length > 0 ? vacancyFilter.join(',') : undefined,
                      sort: value,
                    };
                    
                    if (userSlug) {
                      router.get(route('career.index', userSlug), params, { preserveState: true });
                    } else {
                      router.get('/career', params, { preserveState: true });
                    }
                  }}>
                    <SelectTrigger className="w-full sm:w-40">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="newest">{t("Newest First")}</SelectItem>
                      <SelectItem value="oldest">{t("Oldest First")}</SelectItem>
                      <SelectItem value="salary-high">{t("Salary High to Low")}</SelectItem>
                      <SelectItem value="salary-low">{t("Salary Low to High")}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-2">
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <Input
                      placeholder={t("Search for jobs")}
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="pl-10 w-full sm:w-64"
                    />
                  </div>
                  <Button onClick={handleSearch} className="bg-primary hover:bg-primary/90 text-primary-foreground w-full sm:w-auto">
                    {t("Find Jobs")}
                  </Button>
                </div>
              </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
              {/* Filters Sidebar */}
              <div className="lg:col-span-3">
                <div className="bg-white rounded-lg shadow-sm border p-4 lg:p-6">
                  <div className="flex items-center gap-2 mb-4 lg:mb-6">
                    <Filter className="h-5 w-5 text-blue-500" />
                    <h3 className="text-base lg:text-lg font-semibold text-gray-900">{t("Filter Jobs")}</h3>
                  </div>

                  <div className="space-y-6">
                    {/* Location Filter */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-3">
                        {t("Location")}
                      </label>
                      <Select value={locationFilter} onValueChange={setLocationFilter}>
                        <SelectTrigger>
                          <SelectValue placeholder={t("All Locations")} />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="all">{t("All Locations")}</SelectItem>
                          {dynamicLocations.map(location => (
                            <SelectItem key={location.id} value={location.id.toString()}>{location.name}</SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    {/* Salary Range */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-3">
                        {t("Salary Range")}
                      </label>
                      <Select value={salaryFilter} onValueChange={setSalaryFilter}>
                        <SelectTrigger>
                          <SelectValue placeholder={t("All Ranges")} />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="all">{t("All Ranges")}</SelectItem>
                          <SelectItem value="0-50k">{t("$0 - $50,000")}</SelectItem>
                          <SelectItem value="50k-100k">{t("$50,000 - $100,000")}</SelectItem>
                          <SelectItem value="100k+">{t("$100,000+")}</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>


                    {/* Job Type */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-3">
                        {t("Job Type")}
                      </label>
                      <div className="space-y-2">
                        {dynamicJobTypes.map(jobType => (
                          <label key={jobType.id} className="flex items-center">
                            <input 
                              type="checkbox" 
                              className="rounded border-gray-300 text-blue-600 mr-2" 
                              checked={typeFilter.includes(jobType.id.toString())}
                              onChange={(e) => handleJobTypeChange(jobType.id.toString(), e.target.checked)}
                            />
                            <span className="text-sm text-gray-600">{jobType.name}</span>
                          </label>
                        ))}
                      </div>
                    </div>

                    {/* Vacancies */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-3">
                        {t("Vacancies")}
                      </label>
                      <div className="space-y-2">
                        {vacancyRanges?.map(range => (
                          <label key={range.value} className="flex items-center">
                            <input 
                              type="checkbox" 
                              className="rounded border-gray-300 text-blue-600 mr-2" 
                              checked={vacancyFilter.includes(range.value)}
                              onChange={(e) => handleVacancyChange(range.value, e.target.checked)}
                            />
                            <span className="text-sm text-gray-600">{range.label}</span>
                          </label>
                        ))}
                      </div>
                    </div>

                    {/* Apply Filters Button */}
                    <Button onClick={handleSearch} className="w-full bg-primary hover:bg-primary/90 text-primary-foreground">
                      {t("Apply Filters")}
                    </Button>
                    
                    {/* Reset Filters Button */}
                    <Button 
                      onClick={() => {
                        if (userSlug) {
                          router.get(route('career.index', userSlug));
                        } else {
                          router.get('/career');
                        }
                      }}
                      variant="outline" 
                      className="w-full"
                    >
                      {t("Reset Filters")}
                    </Button>
                  </div>
                </div>
              </div>

              {/* Jobs Grid */}
              <div className="lg:col-span-9">
                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
                  {paginatedJobs.map(job => (
                    <Card key={job.id} className="hover:shadow-lg transition-shadow border border-gray-200 flex flex-col">
                      <CardContent className="p-6 flex flex-col h-full">
                        {/* Vacancies Badge and Location */}
                        <div className="flex items-start justify-between mb-3">
                          <div className="flex items-center gap-2">
                            <Badge className="bg-blue-100 text-blue-700 hover:bg-blue-100 text-xs">
                              {job.positions || 1} {t("Vacancies")}
                            </Badge>
                            {job.is_featured && (
                              <Badge className="bg-yellow-100 text-yellow-700 hover:bg-yellow-100 text-xs">
                                <Star className="h-3 w-3 mr-1" />
                                {t("Featured")}
                              </Badge>
                            )}
                          </div>
                        </div>

                        <h3 className="text-lg font-bold text-gray-900 mb-3 line-clamp-2 leading-tight">
                          {job.title}
                        </h3>

                        {/* Tags */}
                        <div className="flex flex-wrap gap-2 mb-3">
                          {job.skills?.slice(0, 3).map((skill, index) => (
                            <Badge key={index} variant="outline" className="text-xs px-2 py-1">
                              {skill}
                            </Badge>
                          ))}
                        </div>

                        {/* Job Details */}
                        <div className="space-y-1 mb-4">
                          <div className="flex items-center gap-2 text-xs text-gray-600">
                            <Building className="h-3 w-3" />
                            <span>{job.branch?.name || 'General'}</span>
                          </div>
                          <div className="flex items-center gap-2 text-xs text-gray-600">
                            <MapPin className="h-3 w-3" />
                            <span>{job.location?.name || 'Remote'}</span>
                          </div>
                          <div className="flex items-center gap-2 text-xs text-gray-600">
                            <Clock className="h-3 w-3" />
                            <span>{job.job_type?.name || 'Full-time'}</span>
                          </div>
                          <div className="flex items-center gap-2 text-sm text-gray-700 font-medium">
                            <span>💰</span>
                            <span>
                              {job.min_salary && job.max_salary 
                                ? `$${job.min_salary} - $${job.max_salary}`
                                : 'Competitive'
                              }
                            </span>
                          </div>
                        </div>

                        {/* Browse Job Button */}
                        <Button 
                          onClick={() => router.get(route('career.job-details', [userSlug, job.code]))}
                          className="w-full bg-primary hover:bg-primary/90 text-primary-foreground py-2 text-sm font-medium mt-auto"
                        >
                          {t("Browse Job")}
                        </Button>
                      </CardContent>
                    </Card>
                  ))}
                </div>

                {/* Pagination */}
                {totalPages > 1 && (
                  <div className="flex flex-col sm:flex-row justify-center items-center mt-8 lg:mt-12 space-y-2 sm:space-y-0 sm:space-x-2">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => handlePageChange(currentPageFromServer - 1)}
                      disabled={currentPageFromServer === 1}
                      className="flex items-center gap-1 h-8 px-3 w-full sm:w-auto"
                    >
                      <ChevronLeft className="h-3 w-3" />
                      {t("Previous")}
                    </Button>

                    <div className="flex space-x-1">
                      {Array.from({ length: totalPages }, (_, i) => i + 1).map(page => (
                        <Button
                          key={page}
                          variant={currentPageFromServer === page ? "default" : "outline"}
                          size="sm"
                          onClick={() => handlePageChange(page)}
                          className={`w-8 h-8 text-sm ${currentPageFromServer === page ? 'bg-primary text-primary-foreground' : ''}`}
                        >
                          {page}
                        </Button>
                      ))}
                    </div>

                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => handlePageChange(currentPageFromServer + 1)}
                      disabled={currentPageFromServer === totalPages}
                      className="flex items-center gap-1 h-8 px-3 w-full sm:w-auto"
                    >
                      {t("Next")}
                      <ChevronRight className="h-3 w-3" />
                    </Button>
                  </div>
                )}

                {paginatedJobs.length === 0 && (
                  <div className="text-center py-16">
                    <div className="text-gray-300 mb-6">
                      <Search className="h-20 w-20 mx-auto" />
                    </div>
                    <h3 className="text-2xl font-bold text-gray-900 mb-3">{t("No jobs found")}</h3>
                    <p className="text-gray-600 text-lg">
                      {t("Try adjusting your search criteria or check back later for new opportunities.")}
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>
        </section>

        <CareerFooter companySettings={companySettings} />
      </div>
    </>
  );
}