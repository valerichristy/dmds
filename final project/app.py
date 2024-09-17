import pandas as pd
from flask import Flask, render_template, request
from pymongo import MongoClient
from bson.json_util import dumps
from flask_mysqldb import MySQL
import mysql.connector
from flask_cors import CORS
import plotly.io as pio
import plotly.graph_objects as go
from plotly.subplots import make_subplots


client = MongoClient('localhost', 27017)
letterboxd = client['movie_db']

movie_data = letterboxd['movies']
rating_data = letterboxd['ratings_edit']
# user_data = letterboxd['user_export']

genres_opt = movie_data.distinct('genres')
curr_genre = 'All'

# results = movie_data.find().limit(10000)
# mydoc = pd.DataFrame(results, columns=[
#     'genres', 'movie_title', 'spoken_languages', 'popularity', 'vote_average', 'vote_count'])

app = Flask(__name__)
CORS(app, resources={r"/*": {"origins": "http://localhost:5000"}})

# MySQL Conn
app.config['MYSQL_HOST'] = 'localhost'
app.config['MYSQL_USER'] = 'root'
app.config['MYSQL_PASSWORD'] = 'password'
app.config['MYSQL_DB'] = 'movie'
mysql = MySQL(app)


@app.route("/")
def main():
    return render_template('index.html')


@app.route("/find")
def find():
    return render_template('find_movies.html',
                           curr_genre=curr_genre,
                           genres=genres_opt)


@app.route("/filter", methods=['POST'])
def generate_query():
    if request.method == 'POST':
        genre_filter = request.form['genre_filter']
        runtime_filter = int(request.form['runtime_filter'])
        search_filter = request.form['search_filter']

        if genre_filter == 'All':
            results = movie_data.find().limit(10)
        else:
            results = movie_data.find(
                {
                    "movie_title": {"$regex": search_filter},
                    "genres": {"$in": [genre_filter]},
                    "runtime": {"$gt": runtime_filter}
                }).limit(10000).sort("vote_average", -1)
        results = list(results)
        json_data = dumps(results)
        return json_data


@app.route("/recommend")
def rec():
    return render_template('movie_rec.html',
                           genres=genres_opt)


@app.route('/process', methods=['POST'])
def process_input():
    if request.method == 'POST':
        genres = request.form['genres']
        genres = genres.split(',')
        amount = int(request.form['amount'])
        sort = request.form['sort']

        if genres == ['']:
            results = movie_data.find().limit(amount).sort(sort, -1)
        else:
            results = movie_data.find(
                {
                    "genres": {"$in": [genres]},
                }).limit(amount).sort(sort, -1)

        results = list(results)
        json_data = dumps(results) 
        return json_data

@app.route('/user', methods=['GET', 'POST'])
def user_ratings():
    if request.method == 'POST':
        username = request.form.get('username')

        mysql_query = f"SELECT num_reviews FROM users_export_cleaned WHERE username = '{username}'"
        cursor = mysql.connection.cursor()
        cursor.execute(mysql_query)
        review_data = cursor.fetchone()

        if review_data:
            # user_id = review_data[0]
            num_reviews = review_data[0]

            pipeline = [
                {'$match': {'user_id': username}},
                {'$group': {'_id': None, 'count': {'$sum': 1}}}
            ]
            ratings_data = list(rating_data.aggregate(pipeline))
            ratings_count = ratings_data[0]['count'] if ratings_data else 0

            overall_score = calculate_overall_score(num_reviews, ratings_count)
            badge_category = determine_badge_category(overall_score)
            badge_image_url = get_badge_image_url(badge_category)

            metrics = ['Rating Count', 'Review Count']
            counts = [ratings_count, num_reviews]

            fig = make_subplots(rows=1, cols=1)
            fig.add_trace(go.Bar(x=metrics, y=counts))

            fig.update_layout(title=f'Performance for User: {username}', xaxis_title='Metrics', yaxis_title='Counts')

            chart_json = fig.to_json()

            pipeline_genres = [
                {
                    '$match': {'user_id': username}
                },
                {
                    '$lookup': {
                        'from': 'movies',
                        'localField': 'movie_id',
                        'foreignField': 'movie_id',
                        'as': 'movies'
                    }
                },
                {
                    '$unwind': '$movies'
                },
                {
                    '$unwind': '$movies.genres'
                },
                {
                    '$group': {
                        '_id': '$movies.genres',
                        'count': {'$sum': 1}
                    }
                },
                {'$sort': {'count': -1}}
            ]
            genre_distribution = list(rating_data.aggregate(pipeline_genres))
            genre_labels = [genre['_id'] for genre in genre_distribution]
            genre_counts = [genre['count'] for genre in genre_distribution]

            fig = go.Figure(data=[go.Bar(x=genre_labels, y=genre_counts)])
            pie_json = fig.to_json()

            return render_template('user_ratings.html', chart_json=chart_json, pie_json=pie_json, 
                                   username=username, num_reviews=num_reviews,
                                   ratings_count=ratings_count, overall_score=overall_score,
                                   badge_category=badge_category, badge_image_url=badge_image_url)

    return render_template('user_ratings.html')


@app.route('/admin_dashboard')
def admin_dashboard():
    top_films = rating_data.aggregate([
        {
            '$group': {
                '_id': '$movie_id',
                'count': {'$sum': 1}
            }
        },
        {
            '$sort': {'count': -1}
        },
        {
            '$limit': 5
        }
    ])

    # Fetch average rating per movie
    # pipeline_avg_rating = [
    #     {'$group': {'_id': '$movie_id', 'avg_rating': {'$avg': '$rating_val'}}}
    # ]
    # avg_ratings = list(rating_data.aggregate(pipeline_avg_rating))

    # avg_rating_chart = go.Figure(
    #     data=[go.Bar(x=[rating['_id'] for rating in avg_ratings], y=[rating['avg_rating'] for rating in avg_ratings])],
    #     layout=go.Layout(title='Average Rating per Movie')
    # )

    pipeline_rating_distribution = [
        {'$group': {'_id': '$rating_val', 'count': {'$sum': 1}}},
        {'$sort': {'_id': 1}}
    ]
    rating_distribution = list(rating_data.aggregate(pipeline_rating_distribution))

    rating_distribution_chart = go.Figure(
        data=[go.Bar(x=[rating['_id'] for rating in rating_distribution], y=[rating['count'] for rating in rating_distribution])],
        layout=go.Layout(title='Distribution of Ratings')
    )

    film_titles = []
    film_ratings = []
    for film in top_films:
        film_titles.append(film['_id'])
        film_ratings.append(film['count'])

    films_chart = go.Figure([go.Bar(x=film_titles, y=film_ratings)])
    films_chart.update_layout(title='Top 5 Most Rated Films', xaxis_title='Film', yaxis_title='Rating Count')

    pipeline = [
        {
            '$group': {
                '_id': '$user_id',
                'count': {'$sum': 1}
            }
        },
        {
            '$sort': {'count': -1}
        },
        {
            '$limit': 5
        }
    ]
    top_users = list(rating_data.aggregate(pipeline))

    user_ids = []
    overall_scores = []
    for user in top_users:
        user_ids.append(user['_id'])
        overall_scores.append(user['count'])
        
    users_chart = go.Figure([go.Bar(x=user_ids, y=overall_scores)])
    users_chart.update_layout(title='Top 5 Users by Overall Score', xaxis_title='User ID', yaxis_title='Overall Score')

    films_chart_json = films_chart.to_json()
    users_chart_json = users_chart.to_json()
    
    rating_distribution_chart_json = rating_distribution_chart.to_json()

    return render_template('admin_dashboard.html',
                           films_chart_json=films_chart_json,
                           rating_distribution_chart_json=rating_distribution_chart_json, 
                        #    avg_rating_chart_json=avg_rating_chart_json,
                            users_chart_json= users_chart_json)

def calculate_overall_score(num_reviews, num_ratings):
    review_weight = 0.7
    rating_weight = 0.3

    weighted_review_score = num_reviews * review_weight
    weighted_rating_score = num_ratings * rating_weight

    overall_score = weighted_review_score + weighted_rating_score

    return overall_score

def determine_badge_category(overall_score):
    if overall_score >= 300:
        return "diamond"
    elif overall_score >= 200:
        return "platinum"
    elif overall_score >= 100:
        return "gold"
    elif overall_score >= 50:
        return "silver"
    else:
        return "bronze"

def get_badge_image_url(badge_category):
    if badge_category == "diamond":
        return "/static/images/diamond_badge.png"
    elif badge_category == "platinum":
        return "/static/images/platinum_badge.png"
    elif badge_category == "gold":
        return "/static/images/gold_badge.png"
    elif badge_category == "silver":
        return "/static/images/silver_badge.png"
    else:
        return "/static/images/bronze_badge.png"

if __name__ == '__main__':
    app.run(debug=True)
