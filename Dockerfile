FROM python:latest
# copy requirements before rest of app https://stackoverflow.com/a/34399661/2624391
COPY requirements.txt /src/requirements.txt
WORKDIR /src
RUN pip install -r requirements.txt
COPY . /src
CMD ["python", "test.py"]